<?php

namespace App\Controller\Tasks;

use App\Entity\Tasks;
use App\Service\PermissionService;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TaskController extends AbstractController
{
    private TaskService $taskService;
    private PermissionService $permissionService;

    public function __construct(
        TaskService $taskService,
        PermissionService $permissionService
    ) {
        $this->taskService = $taskService;
        $this->permissionService = $permissionService;
    }

    #[Route('/task/{id}', name: 'app_details_tasks')]
    public function details(int $id, Request $request): Response
    {
        $task = $this->taskService->getTaskWithFullData($id);
        
        if (!$task) {
            throw $this->createNotFoundException('Tâche non trouvée');
        }
        
        // Vérifier si l'utilisateur peut voir cette tâche
        if (!$this->permissionService->canViewTask($task)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour voir cette tâche.');
        }
        
        // Récupérer les commentaires et pièces jointes
        $comments = $this->taskService->getCommentsByTask($task);
        $attachments = $this->taskService->getAttachmentsByTask($task);
        
        // Traiter l'ajout de commentaire
        if ($request->isMethod('POST') && $request->request->has('comment')) {
            $commentContent = trim($request->request->get('comment'));
            if (!empty($commentContent)) {
                $this->taskService->createComment($task, $commentContent);
                return $this->redirectToRoute('app_details_tasks', ['id' => $id]);
            }
        }
        
        // Traiter l'ajout de pièce jointe
        if ($request->isMethod('POST') && $request->files->has('attachment')) {
            $file = $request->files->get('attachment');
            if ($file instanceof UploadedFile) {
                $description = $request->request->get('attachment_description', '');
                $this->taskService->uploadAttachment($task, $file, $description);
                return $this->redirectToRoute('app_details_tasks', ['id' => $id]);
            }
        }
        
        return $this->render('project/details_task.html.twig', [
            'task' => $task,
            'comments' => $comments,
            'attachments' => $attachments,
            'canEdit' => $this->permissionService->canEditTask($task),
            'canAddComment' => $this->permissionService->canAddComment($task),
            'canAddAttachment' => $this->permissionService->canAddAttachment($task),
        ]);
    }
    
    #[Route('/task/{id}/update-status', name: 'app_update_task_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $task = $this->taskService->getTaskWithFullData($id);
        
        if (!$task) {
            return $this->json(['success' => false, 'message' => 'Tâche non trouvée']);
        }
        
        if (!$this->permissionService->canEditTask($task)) {
            return $this->json(['success' => false, 'message' => 'Vous n\'avez pas les permissions nécessaires']);
        }
        
        $newStatus = $request->request->get('status');
        if (empty($newStatus)) {
            return $this->json(['success' => false, 'message' => 'Statut non spécifié']);
        }
        
        try {
            $this->taskService->updateTaskStatus($task, $newStatus);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
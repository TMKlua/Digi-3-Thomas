<?php

namespace App\Controller;

use App\Entity\Tasks;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PermissionService $permissionService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PermissionService $permissionService
    ) {
        $this->entityManager = $entityManager;
        $this->permissionService = $permissionService;
    }

    #[Route('/task/{id}', name: 'app_details_tasks')]
    public function details(int $id): Response
    {
        $task = $this->entityManager->getRepository(Tasks::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException('La tâche n\'existe pas.');
        }

        // Vérifier si l'utilisateur a le droit de voir cette tâche
        if (!$this->permissionService->canEditTask($task) && 
            $task->getTaskAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour voir cette tâche.');
        }

        return $this->render('project/details_task.html.twig', [
            'task' => $task,
            'canEdit' => $this->permissionService->canEditTask($task),
            'canAddComment' => $this->permissionService->canAddComment($task),
            'canAddAttachment' => $this->permissionService->canAddAttachment($task),
        ]);
    }
}
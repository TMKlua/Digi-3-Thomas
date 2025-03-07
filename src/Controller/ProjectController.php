<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Form\ProjectType;
use App\Form\TaskType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PermissionService;

class ProjectController extends AbstractController
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    #[Route('/management-project/{id}', name: 'app_management_project')]
    public function managementProject(
        ProjectRepository $projectRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ?int $id = null
    ): Response {
        // Vérifier si l'utilisateur peut créer un projet en utilisant le Voter
        $this->denyAccessUnlessGranted('create', null, 'Vous n\'avez pas les permissions nécessaires pour gérer les projets.');

        // Création d'un nouveau projet
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
    
        // Création d'une nouvelle tâche
        $task = new Tasks();
        $taskForm = $this->createForm(TaskType::class, $task);
    
        // Traiter la soumission des formulaires
        $form->handleRequest($request);
        $taskForm->handleRequest($request);
    
        // Récupérer les projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(['projectManager' => $this->getUser()]);
    
        // Identifier le projet courant (sélectionné)
        $currentProject = null;
        if ($id) {
            $currentProject = $projectRepository->find($id);
            
            // Vérifier si l'utilisateur peut voir ce projet en utilisant le Voter
            if (!$currentProject) {
                $this->addFlash('error', 'Projet introuvable.');
                return $this->redirectToRoute('app_management_project');
            }
            
            try {
                $this->denyAccessUnlessGranted('view', $currentProject);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Vous n\'êtes pas autorisé à accéder à ce projet.');
                return $this->redirectToRoute('app_management_project');
            }
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$project->getProjectStartDate()) {
                $project->setProjectStartDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }
            $project->setProjectManager($this->getUser());
    
            $entityManager->persist($project);
            $entityManager->flush();
    
            $this->addFlash('success', 'Projet créé avec succès !');
            return $this->redirectToRoute('app_management_project');
        }
    
        if ($taskForm->isSubmitted() && $taskForm->isValid()) {
            if (!$task->getTaskStartDate()) {
                $task->setTaskStartDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }
    
            if ($currentProject) {
                // Vérifier si l'utilisateur peut créer une tâche dans ce projet en utilisant le Voter
                $this->denyAccessUnlessGranted('create', $currentProject, 'Vous n\'avez pas les permissions nécessaires pour créer une tâche dans ce projet.');
                
                $task->setTaskProject($currentProject);
            } else {
                $this->addFlash('error', 'Aucun projet sélectionné pour cette tâche.');
                return $this->redirectToRoute('app_management_project');
            }
    
            $entityManager->persist($task);
            $entityManager->flush();
    
            $this->addFlash('success', 'Tâche ajoutée avec succès !');
            return $this->redirectToRoute('app_management_project', ['id' => $currentProject->getId()]);
        }
    
        return $this->render('project/management_project.html.twig', [
            'projects' => $projects,
            'current_project' => $currentProject,
            'form' => $form->createView(),
            'taskForm' => $taskForm->createView(),
            'tasks' => $currentProject ? $currentProject->getTasks() : [],
        ]);
    }

    #[Route('/management-project/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function deleteProject(Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur peut supprimer ce projet en utilisant le Voter
        $this->denyAccessUnlessGranted('delete', $project, 'Vous n\'avez pas les permissions nécessaires pour supprimer ce projet.');

        $entityManager->remove($project);
        $entityManager->flush();

        $this->addFlash('success', 'Projet supprimé avec succès !');
        return $this->redirectToRoute('app_management_project');
    }

    #[Route('/management-project/update-task-status', name: 'app_update_task_status', methods: ['POST'])]
    public function updateTaskStatus(Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = json_decode($request->getContent(), true);

        // Vérifier que les données nécessaires sont fournies
        if (!isset($content['taskId'], $content['newStatus'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer la tâche par son ID
        $task = $entityManager->getRepository(Tasks::class)->find($content['taskId']);
        if (!$task) {
            return $this->json(['error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur peut changer le statut de cette tâche en utilisant le Voter
        try {
            $this->denyAccessUnlessGranted('change_status', $task);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Vous n\'avez pas les permissions nécessaires'], Response::HTTP_FORBIDDEN);
        }

        // Mettre à jour le statut de la tâche
        $task->setTaskStatus($content['newStatus']);
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(['success' => 'Statut de la tâche mis à jour'], Response::HTTP_OK);
    }
}

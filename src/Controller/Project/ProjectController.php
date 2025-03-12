<?php

namespace App\Controller\Project;

use App\Entity\Project;
use App\Entity\Tasks;
use App\Form\ProjectType;
use App\Form\TaskType;
use App\Service\ProjectService;
use App\Service\TaskService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/project')]
class ProjectController extends AbstractController
{
    public function __construct(
        private PermissionService $permissionService,
        private ProjectService $projectService,
        private TaskService $taskService
    ) {
    }

    #[Route('/manage/{id}', name: 'app_management_project')]
    public function managementProject(
        Request $request,
        ?int $id = null
    ): Response {
        // Vérifier si l'utilisateur peut créer un projet
        if (!$this->permissionService->canCreateProject()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour gérer les projets.');
        }

        // Création d'un nouveau projet
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        
        // Création d'une nouvelle tâche
        $task = new Tasks();
        $taskForm = $this->createForm(TaskType::class, $task);
        
        // Récupération du projet existant si l'ID est fourni
        $currentProject = null;
        if ($id) {
            $currentProject = $this->projectService->getProjectById($id);
            if (!$currentProject) {
                throw $this->createNotFoundException('Projet non trouvé');
            }
            
            // Vérifier si l'utilisateur peut voir ce projet
            if (!$this->permissionService->canViewProject($currentProject)) {
                throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour voir ce projet.');
            }
        }
        
        // Traitement du formulaire de projet
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder le projet
            $this->projectService->saveProject($project);
            
            return $this->redirectToRoute('app_management_project', ['id' => $project->getId()]);
        }
        
        // Traitement du formulaire de tâche
        $taskForm->handleRequest($request);
        if ($taskForm->isSubmitted() && $taskForm->isValid() && $currentProject) {
            // Vérifier si l'utilisateur peut créer une tâche dans ce projet
            if (!$this->permissionService->canCreateTask($currentProject)) {
                throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour créer une tâche dans ce projet.');
            }
            
            // Créer la tâche
            $task->setTaskProject($currentProject);
            $this->taskService->createTask([
                'name' => $task->getTaskName(),
                'description' => $task->getTaskDescription(),
                'status' => 'new',
                'project' => $currentProject,
                'assignee' => $task->getTaskAssignedTo()
            ]);
            
            return $this->redirectToRoute('app_management_project', ['id' => $currentProject->getId()]);
        }
        
        // Récupérer les tâches du projet si un projet est sélectionné
        $tasks = $currentProject ? $this->taskService->getTasksByProject($currentProject) : [];
        
        return $this->render('project/management.html.twig', [
            'form' => $form->createView(),
            'taskForm' => $taskForm->createView(),
            'project' => $currentProject,
            'tasks' => $tasks,
            'canCreateTask' => $currentProject && $this->permissionService->canCreateTask($currentProject),
            'canEditProject' => $currentProject && $this->permissionService->canEditProject($currentProject),
            'canDeleteProject' => $currentProject && $this->permissionService->canDeleteProject()
        ]);
    }

    #[Route('/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function deleteProject(int $id): Response
    {
        // Récupérer le projet
        $project = $this->projectService->getProjectById($id);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé');
        }
        
        // Vérifier si l'utilisateur peut supprimer ce projet
        if (!$this->permissionService->canDeleteProject()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les permissions nécessaires pour supprimer ce projet.');
        }
        
        // Supprimer le projet
        $this->projectService->deleteProject($project);
        
        return $this->redirectToRoute('app_management_project');
    }

    #[Route('/list', name: 'app_project_list')]
    public function listProjects(): Response
    {
        // Récupérer tous les projets que l'utilisateur peut voir
        $projects = $this->projectService->getProjectsForCurrentUser();
        
        return $this->render('project/list.html.twig', [
            'projects' => $projects,
            'canCreateProject' => $this->permissionService->canCreateProject()
        ]);
    }

    #[Route('/dashboard', name: 'app_project_dashboard')]
    public function dashboard(): Response
    {
        // Récupérer les statistiques des projets
        $stats = $this->projectService->getProjectStatistics();
        
        return $this->render('project/dashboard.html.twig', [
            'stats' => $stats
        ]);
    }
}

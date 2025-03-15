<?php

namespace App\Controller;

use App\Entity\ManagerProject;
use App\Entity\Tasks;
use App\Form\ManagerProjectType;
use App\Form\TaskType;
use App\Repository\ManagerProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ManagementProjectController extends AbstractController
{
    #[Route('/management-project/{id<\d+>?}', name: 'app_management_project')]
    public function managementProject(
        ManagerProjectRepository $projectRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ?string $id = null
    ): Response {
        $id = $id !== null ? (int) $id : null;
        
        $project = new ManagerProject();
        $form = $this->createForm(ManagerProjectType::class, $project);
    
        $task = new Tasks();
        $task->setTaskRank(1); // Définir la valeur par défaut du taskRank
        $taskForm = $this->createForm(TaskType::class, $task);
    
        $form->handleRequest($request);
        $taskForm->handleRequest($request);
    
        $projects = $projectRepository->findBy(['projectLeader' => $this->getUser()]);
    
        $currentProject = null;
        if ($id) {
            $currentProject = $projectRepository->find($id);
            if (!$currentProject || $currentProject->getProjectLeader() !== $this->getUser()) {
                $this->addFlash('error', 'Projet introuvable ou non autorisé.');
                return $this->redirectToRoute('app_management_project');
            }
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$project->getStartDate()) {
                $project->setStartDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }
            $project->setProjectLeader($this->getUser());
    
            $entityManager->persist($project);
            $entityManager->flush();
    
            $this->addFlash('success', 'Projet créé avec succès !');
            return $this->redirectToRoute('app_management_project');
        }
    
        if ($taskForm->isSubmitted() && $taskForm->isValid()) {
            if (!$task->getTaskDateFrom()) {
                $task->setTaskDateFrom(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }
            
            if ($currentProject) {
                $task->setProject($currentProject);
                
                $entityManager->persist($task);
                $entityManager->flush();
                
                $this->updateTaskRank($entityManager, $currentProject);
            } else {
                $this->addFlash('error', 'Aucun projet sélectionné pour cette tâche.');
                return $this->redirectToRoute('app_management_project');
            }
    
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

    private function updateTaskRank(EntityManagerInterface $entityManager, ManagerProject $project): void
    {
        $tasks = $project->getTasks();
        $rank = 1;
        foreach ($tasks as $task) {
            $task->setTaskRank($rank);
        }
        $entityManager->flush();
    }

    
    #[Route('/management-project/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function deleteProject(ManagerProject $project, EntityManagerInterface $entityManager): Response
    {
        if ($project->getProjectLeader() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce projet.');
        }

        $entityManager->remove($project);
        $entityManager->flush();

        $this->addFlash('success', 'Projet supprimé avec succès !');
        return $this->redirectToRoute('app_management_project');
    }

    #[Route('/management-project/update-task-status', name: 'app_update_task_status', methods: ['POST'])]
    public function updateTaskStatus(Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = json_decode($request->getContent(), true);

        if (!isset($content['taskId'], $content['newStatus'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $task = $entityManager->getRepository(Tasks::class)->find($content['taskId']);
        if (!$task) {
            return $this->json(['error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }

        $task->setTaskStatus($content['newStatus']);
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(['success' => 'Statut de la tâche mis à jour'], Response::HTTP_OK);
    }

    #[Route('/management-project/update-task-position', name: 'app_update_task_position', methods: ['POST'])]
    public function updateTaskPosition(Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = json_decode($request->getContent(), true);
    
        if (!isset($content['taskId'], $content['newColumn'], $content['taskOrder'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }
    
        $columnRanks = [
            'a-faire' => 1,
            'bloque' => 2,
            'en-cours' => 3,
            'terminee' => 4,
        ];
    
        if (!isset($columnRanks[$content['newColumn']])) {
            return $this->json(['error' => 'Colonne invalide'], Response::HTTP_BAD_REQUEST);
        }
    
        $task = $entityManager->getRepository(Tasks::class)->find($content['taskId']);
        if (!$task) {
            return $this->json(['error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }
    
        $task->setTaskColumnRank($columnRanks[$content['newColumn']]);
        
        foreach ($content['taskOrder'] as $taskData) {
            $taskToUpdate = $entityManager->getRepository(Tasks::class)->find($taskData['id']);
            if ($taskToUpdate) {
                $taskToUpdate->setTaskRank($taskData['rank']);
                $entityManager->persist($taskToUpdate);
            }
        }
    
        $entityManager->flush();
    
        return $this->json(['success' => 'Position et colonne des tâches mises à jour'], Response::HTTP_OK);
    }
}

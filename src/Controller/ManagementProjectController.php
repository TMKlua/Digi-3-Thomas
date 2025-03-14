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
    #[Route('/management-project/{id}', name: 'app_management_project')]
    public function managementProject(
        ManagerProjectRepository $projectRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ?int $id = null
    ): Response {
        $project = new ManagerProject();
        $form = $this->createForm(ManagerProjectType::class, $project);
    
        $task = new Tasks();
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
                
                // Définir le rang en fonction du nombre de tâches existantes
                $task->setTaskRanks($this->getNextTaskRank($currentProject));
                $entityManager->persist($task);
                $entityManager->flush();
                
                $this->updateTaskRanks($entityManager, $currentProject);
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

    private function updateTaskRanks(EntityManagerInterface $entityManager, ManagerProject $project): void
    {
        $tasks = $project->getTasks();
        $rank = 1;
        foreach ($tasks as $task) {
            $task->setTaskRanks($rank++);
        }
        $entityManager->flush();
    }

    private function getNextTaskRank(ManagerProject $project): int
    {
        return count($project->getTasks());
    }

    #[Route('/management-project/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function deleteProject(ManagerProject $project, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si le projet appartient à l'utilisateur connecté
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

        // Vérifier que les données nécessaires sont fournies
        if (!isset($content['taskId'], $content['newStatus'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer la tâche par son ID
        $task = $entityManager->getRepository(Tasks::class)->find($content['taskId']);
        if (!$task) {
            return $this->json(['error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Mettre à jour le statut de la tâche
        $task->setTaskStatus($content['newStatus']);
        $entityManager->persist($task);
        $entityManager->flush();

        return $this->json(['success' => 'Statut de la tâche mis à jour'], Response::HTTP_OK);
    }

    #[Route('/management-project/update-task-position', name: 'app_update_task_position', methods: ['POST'])]
    public function updateTaskPosition(Request $request, EntityManagerInterface $entityManager): Response
    {
        $content = json_decode($request->getContent(), true);

        // Vérifier que les données nécessaires sont fournies
        if (!isset($content['taskId'], $content['newStatus'], $content['taskOrder'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer la tâche par son ID
        $task = $entityManager->getRepository(Tasks::class)->find($content['taskId']);
        if (!$task) {
            return $this->json(['error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Mettre à jour le statut de la tâche
        $task->setTaskStatus($content['newStatus']);

        // Mettre à jour l'ordre des tâches dans la colonne
        foreach ($content['taskOrder'] as $taskData) {
            $taskToUpdate = $entityManager->getRepository(Tasks::class)->find($taskData['id']);
            if ($taskToUpdate) {
                $taskToUpdate->setRank($taskData['rank']);
                $entityManager->persist($taskToUpdate);
            }
        }

        $entityManager->flush();

        return $this->json(['success' => 'Position des tâches mise à jour'], Response::HTTP_OK);
    }
}

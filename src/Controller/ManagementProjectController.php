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
        // Création d'un nouveau projet
        $project = new ManagerProject();
        $form = $this->createForm(ManagerProjectType::class, $project);
    
        // Création d'une nouvelle tâche
        $task = new Tasks();
        $taskForm = $this->createForm(TaskType::class, $task);
    
        // Traiter la soumission des formulaires
        $form->handleRequest($request);
        $taskForm->handleRequest($request);
    
        // Récupérer les projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(['projectLeader' => $this->getUser()]);
    
        // Identifier le projet courant (sélectionné)
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
}

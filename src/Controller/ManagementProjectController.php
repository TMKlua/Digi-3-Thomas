<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ManagementProjectController extends AbstractController
{
    #[Route('/management-project', name: 'app_management_project')]
    public function managementProject(ProjectRepository $projectRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création d'un nouveau projet
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Définir automatiquement la date de début si elle n'est pas définie
            if (!$project->getStartDate()) {
                $timezone = new \DateTimeZone('Europe/Paris');
                $currentDate = new \DateTime('now', $timezone);
                $project->setStartDate($currentDate);
            }

            // Assigner l'utilisateur connecté comme chef de projet
            $project->setProjectLeader($this->getUser());

            // Sauvegarder le projet dans la base de données
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet créé avec succès !');
            return $this->redirectToRoute('app_management_project');
        }

        // Récupérer les projets de l'utilisateur actuellement connecté
        $projects = $projectRepository->findBy(['projectLeader' => $this->getUser()]);

        return $this->render('project/management_project.html.twig', [
            'controller_name' => 'ManagementProjectController',
            'projects' => $projects,
            'current_project' => $projects[0] ?? null,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/management-project/delete/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function deleteProject(Project $project, EntityManagerInterface $entityManager): Response
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

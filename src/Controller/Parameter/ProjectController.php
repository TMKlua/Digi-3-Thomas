<?php

namespace App\Controller\Parameter;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parameter/projects')]
#[IsGranted('ROLE_PROJECT_MANAGER')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_parameter_projects')]
    public function index(): Response
    {
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur peut voir la liste des projets
        if (!$this->permissionService->canViewProjectList()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $projects = $this->projectRepository->findAll();
        $canEdit = $this->permissionService->canEditProject();

        return $this->render('parameter/projects.html.twig', [
            'projects' => $projects,
            'user' => $currentUser,
            'canEdit' => $canEdit
        ]);
    }

    #[Route('/add', name: 'app_parameter_project_add', methods: ['GET', 'POST'])]
    public function add(): Response
    {
        // Logique pour ajouter un nouveau projet
        return $this->render('parameter/project_add.html.twig');
    }

    #[Route('/edit/{id}', name: 'app_parameter_project_edit', methods: ['GET', 'POST'])]
    public function edit(int $id): Response
    {
        // Logique pour éditer un projet existant
        return $this->render('parameter/project_edit.html.twig', [
            'projectId' => $id
        ]);
    }

    #[Route('/delete/{id}', name: 'app_parameter_project_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        // Logique pour supprimer un projet
        // Dans une implémentation réelle, vous géreriez la suppression et retourneriez une réponse JSON
        return $this->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ]);
    }
}

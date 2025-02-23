<?php

// src/Controller/HomeController.php
namespace App\Controller;

use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class HomeController extends AbstractController
{
    public function __construct(
        private Security $security,
        private PermissionService $permissionService
    ) {}

    #[Route('/', name: 'home_index')]
    public function index(): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'user' => $user,
            'canViewDashboard' => $this->permissionService->hasPermission('view_dashboard'),
            'canViewProjects' => $this->permissionService->hasPermission('view_projects'),
            'canViewTasks' => $this->permissionService->hasPermission('view_team_tasks'),
        ]);
    }
}

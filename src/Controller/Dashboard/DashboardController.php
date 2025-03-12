<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'user' => $user,
            'canViewStatistics' => $this->permissionService->canViewStatistics(),
            'canManageUsers' => $this->permissionService->canManageUsers(),
            'canManageCustomers' => $this->permissionService->canEditCustomer(),
            'canCreateProject' => $this->permissionService->canCreateProject(),
        ]);
    }
}

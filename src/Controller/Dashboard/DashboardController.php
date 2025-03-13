<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use App\Service\PermissionService;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private PermissionService $permissionService,
        private SecurityService $securityService
    ) {}

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->securityService->getCurrentUser();
        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'canViewStatistics' => $this->permissionService->canViewStatistics(),
            'canManageUsers' => $this->permissionService->canManageUsers(),
            'canManageCustomers' => $this->permissionService->canEditCustomer(),
            'canCreateProject' => $this->permissionService->canCreateProject(),
        ]);
    }
}

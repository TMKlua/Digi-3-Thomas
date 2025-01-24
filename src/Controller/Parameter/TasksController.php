<?php

namespace App\Controller\Parameter;

use App\Entity\Tasks;
use App\Repository\TasksRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/parameter/tasks')]
class TasksController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TasksRepository $tasksRepository,
        private PermissionService $permissionService,
        private Security $security
    ) {}

    #[Route('/', name: 'app_parameter_tasks')]
    public function index(): Response
    {
        $currentUser = $this->security->getUser();

        if (!$this->permissionService->canViewProjectList()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $tasks = $this->tasksRepository->findAll();
        $canEdit = $this->permissionService->canEditProject();
        $canDelete = $this->permissionService->canDeleteProject();

        return $this->render('parameter/tasks.html.twig', [
            'projects' => $tasks,
            'user' => $currentUser,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete
        ]);
    }

    #[Route('/add', name: 'app_parameter_task_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        if (!$this->permissionService->canEditProject()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = $request->request->all();
            
            $task = new Tasks();
            $task->setTaskName($data['name'])
                ->setTaskText($data['description'])
                ->setTaskComplexity($data['complexity'])
                ->setTaskPriority($data['priority'])
                ->setTaskTargetStartDate(new \DateTime($data['targetStartDate']))
                ->setTaskTargetEndDate(new \DateTime($data['targetEndDate']));

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Tâche créée avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/edit/{id}', name: 'app_parameter_task_edit', methods: ['POST'])]
    public function edit(Request $request, Tasks $task): JsonResponse
    {
        if (!$this->permissionService->canEditProject()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = $request->request->all();
            
            $task->setTaskName($data['name'])
                ->setTaskText($data['description'])
                ->setTaskComplexity($data['complexity'])
                ->setTaskPriority($data['priority'])
                ->setTaskTargetStartDate(new \DateTime($data['targetStartDate']))
                ->setTaskTargetEndDate(new \DateTime($data['targetEndDate']));

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Tâche modifiée avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{id}', name: 'app_parameter_task_delete', methods: ['POST'])]
    public function delete(Tasks $task): JsonResponse
    {
        if (!$this->permissionService->canDeleteProject()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->remove($task);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Tâche supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}

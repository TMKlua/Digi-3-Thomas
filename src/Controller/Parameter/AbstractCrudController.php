<?php

namespace App\Controller\Parameter;

use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;

abstract class AbstractCrudController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ValidatorInterface $validator,
        protected LoggerInterface $logger,
        protected PermissionService $permissionService,
        protected Security $security
    ) {}

    abstract protected function getEntityClass(): string;
    abstract protected function getRepository();
    abstract protected function canView(): bool;
    abstract protected function canEdit(): bool;
    abstract protected function canDelete(): bool;
    abstract protected function validateData(array $data): void;
    abstract protected function createEntity(array $data): object;
    abstract protected function updateEntity(object $entity, array $data): void;
    abstract protected function getEntityName(): string;
    abstract protected function getTemplatePrefix(): string;

    protected function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié');
        }

        if (!$this->canView()) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }

        $entities = $this->getRepository()->findAll();
        $canEdit = $this->canEdit();
        $canDelete = $this->canDelete();

        return $this->render($this->getTemplatePrefix() . '/index.html.twig', [
            'user' => $currentUser,
            'entities' => $entities,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'entity_name' => $this->getEntityName(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        if (!$this->canEdit()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = $this->getRequestData($request);
            $this->validateData($data);

            $entity = $this->createEntity($data);
            
            $errors = $this->validator->validate($entity);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->logger->info('Entité créée', [
                'type' => $this->getEntityName(),
                'id' => $entity->getId()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Entité créée avec succès',
                'id' => $entity->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de création', [
                'type' => $this->getEntityName(),
                'message' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['POST'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $entity = $this->getRepository()->find($id);
        
        if (!$entity) {
            return $this->json([
                'success' => false,
                'error' => 'Entité non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canEdit()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $data = $this->getRequestData($request);
            $this->validateData($data);

            $this->updateEntity($entity, $data);
            
            $errors = $this->validator->validate($entity);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => (string) $errors
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            $this->logger->info('Entité modifiée', [
                'type' => $this->getEntityName(),
                'id' => $entity->getId()
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Entité modifiée avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de modification', [
                'type' => $this->getEntityName(),
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $entity = $this->getRepository()->find($id);
        
        if (!$entity) {
            return $this->json([
                'success' => false,
                'error' => 'Entité non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canDelete()) {
            return $this->json([
                'success' => false,
                'error' => 'Vous n\'avez pas les permissions nécessaires'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            $this->logger->info('Entité supprimée', [
                'type' => $this->getEntityName(),
                'id' => $id
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Entité supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur de suppression', [
                'type' => $this->getEntityName(),
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    protected function getRequestData(Request $request): array
    {
        return $request->request->all();
    }
} 
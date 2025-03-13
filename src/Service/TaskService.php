<?php

namespace App\Service;

use App\Entity\Tasks;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\TasksAttachments;
use App\Entity\TasksComments;
use App\Enum\TaskStatus;
use App\Repository\TasksRepository;
use App\Repository\TasksAttachmentsRepository;
use App\Repository\TasksCommentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service centralisé pour la gestion des tâches, commentaires et pièces jointes
 */
class TaskService
{
    private string $attachmentsDirectory;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TasksRepository $taskRepository,
        private TasksAttachmentsRepository $attachmentsRepository,
        private TasksCommentsRepository $commentsRepository,
        private PermissionService $permissionService,
        private SecurityService $securityService,
        private ?SluggerInterface $slugger = null,
        ?string $attachmentsDirectory = null
    ) {
        $this->attachmentsDirectory = $attachmentsDirectory ?? sys_get_temp_dir();
    }

    // ===== GESTION DES TÂCHES =====

    /**
     * Crée une nouvelle tâche
     */
    public function createTask(array $data): Tasks
    {
        if (!$this->permissionService->hasPermission('create_task')) {
            throw new \RuntimeException('Permission denied to create task');
        }

        $task = new Tasks();
        $task->setTaskName($data['name']);
        $task->setTaskDescription($data['description'] ?? '');
        
        // Convertir la chaîne de statut en enum TaskStatus
        $statusValue = $data['status'] ?? 'new';
        $status = TaskStatus::from($statusValue);
        $task->setTaskStatus($status);
        
        if (isset($data['project']) && $data['project'] instanceof Project) {
            $task->setTaskProject($data['project']);
        }
        
        if (isset($data['assignee']) && $data['assignee'] instanceof User) {
            $task->setTaskAssignedTo($data['assignee']);
        }
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
        return $task;
    }

    /**
     * Met à jour le statut d'une tâche
     */
    public function updateTaskStatus(Tasks $task, string $newStatus): Tasks
    {
        if (!$this->permissionService->canEditTask($task)) {
            throw new \RuntimeException('Permission denied to update task status');
        }
        
        // Convertir la chaîne de statut en enum TaskStatus
        $status = TaskStatus::from($newStatus);
        $task->setTaskStatus($status);
        $this->entityManager->flush();
        
        return $task;
    }

    /**
     * Assigne une tâche à un utilisateur
     */
    public function assignTask(Tasks $task, User $assignee): Tasks
    {
        if (!$this->permissionService->canAssignTask()) {
            throw new \RuntimeException('Permission denied to assign task');
        }
        
        $task->setTaskAssignedTo($assignee);
        $this->entityManager->flush();
        
        return $task;
    }

    /**
     * Récupère les tâches d'un projet
     */
    public function getTasksByProject(Project $project): array
    {
        return $this->taskRepository->findBy(['taskProject' => $project]);
    }

    /**
     * Récupère les tâches assignées à un utilisateur
     */
    public function getTasksByAssignee(User $user): array
    {
        return $this->taskRepository->findBy(['taskAssignedTo' => $user]);
    }

    /**
     * Récupère les tâches par statut
     */
    public function getTasksByStatus(TaskStatus $status): array
    {
        return $this->taskRepository->findBy(['taskStatus' => $status]);
    }

    /**
     * Récupère les tâches par priorité
     */
    public function getTasksByPriority(string $priority): array
    {
        return $this->taskRepository->findBy(['taskPriority' => $priority]);
    }

    /**
     * Récupère une tâche avec toutes ses données associées
     */
    public function getTaskWithFullData(int $id): ?Tasks
    {
        return $this->taskRepository->findWithFullData($id);
    }

    /**
     * Recherche des tâches selon des filtres
     */
    public function searchTasks(array $filters): array
    {
        return $this->taskRepository->search($filters);
    }

    // ===== GESTION DES COMMENTAIRES =====

    /**
     * Crée un nouveau commentaire pour une tâche
     */
    public function createComment(Tasks $task, string $content): TasksComments
    {
        if (!$this->permissionService->canViewTask($task)) {
            throw new \RuntimeException('Permission denied to comment on this task');
        }

        $comment = new TasksComments();
        $comment->setTask($task);
        $comment->setContent($content);
        $comment->setUser($this->securityService->getCurrentUser());
        // La date de création est définie dans le constructeur de TasksComments

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    /**
     * Récupère les commentaires d'une tâche
     */
    public function getCommentsByTask(Tasks $task): array
    {
        return $this->commentsRepository->findBy(
            ['task' => $task],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * Récupère les commentaires de l'utilisateur courant
     */
    public function getCommentsByUser(): array
    {
        $user = $this->securityService->getCurrentUser();
        return $this->commentsRepository->findBy(['user' => $user]);
    }

    /**
     * Récupère les commentaires récents
     */
    public function getRecentComments(int $limit = 10): array
    {
        return $this->commentsRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    /**
     * Recherche des commentaires
     */
    public function searchComments(string $term): array
    {
        return $this->commentsRepository->search($term);
    }

    // ===== GESTION DES PIÈCES JOINTES =====

    /**
     * Télécharge une pièce jointe pour une tâche
     */
    public function uploadAttachment(Tasks $task, UploadedFile $file, ?string $description = null): TasksAttachments
    {
        if (!$this->permissionService->canViewTask($task)) {
            throw new \RuntimeException('Permission denied to upload attachment to this task');
        }

        // Vérifier que le slugger est disponible
        if (!$this->slugger) {
            throw new \RuntimeException('Slugger service is not available');
        }

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Déplacer le fichier dans le répertoire des pièces jointes
        try {
            $file->move($this->attachmentsDirectory, $newFilename);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        // Créer l'entité de pièce jointe
        $attachment = new TasksAttachments();
        $attachment->setTask($task);
        $attachment->setName($newFilename);
        $attachment->setOriginalName($file->getClientOriginalName());
        $attachment->setMimeType($file->getMimeType());
        $attachment->setFileSize($file->getSize());
        $attachment->setDescription($description);
        $attachment->setUploadedBy($this->securityService->getCurrentUser());
        // La date de création est définie dans le constructeur de TasksAttachments

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $attachment;
    }

    /**
     * Récupère les pièces jointes d'une tâche
     */
    public function getAttachmentsByTask(Tasks $task): array
    {
        return $this->attachmentsRepository->findBy(
            ['task' => $task],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * Supprime une pièce jointe
     */
    public function deleteAttachment(int $id): void
    {
        $attachment = $this->attachmentsRepository->find($id);
        
        if (!$attachment) {
            throw new \RuntimeException('Attachment not found');
        }
        
        if (!$this->canAccessAttachment($attachment)) {
            throw new \RuntimeException('Permission denied to delete this attachment');
        }
        
        // Supprimer le fichier physique
        $filePath = $this->getAttachmentPath($attachment);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Supprimer l'entité
        $this->entityManager->remove($attachment);
        $this->entityManager->flush();
    }

    /**
     * Récupère les pièces jointes récentes
     */
    public function getRecentAttachments(int $limit = 10): array
    {
        return $this->attachmentsRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    /**
     * Recherche des pièces jointes
     */
    public function searchAttachments(string $term): array
    {
        return $this->attachmentsRepository->search($term);
    }

    /**
     * Récupère le chemin complet d'une pièce jointe
     */
    public function getAttachmentPath(TasksAttachments $attachment): string
    {
        return $this->attachmentsDirectory . '/' . $attachment->getName();
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une pièce jointe
     */
    public function canAccessAttachment(TasksAttachments $attachment): bool
    {
        $user = $this->securityService->getCurrentUser();
        if (!$user instanceof User) {
            return false;
        }
        
        $task = $attachment->getTask();
        
        // L'utilisateur qui a téléchargé la pièce jointe peut y accéder
        if ($attachment->getUploadedBy() === $user) {
            return true;
        }
        
        // L'utilisateur assigné à la tâche peut accéder à la pièce jointe
        if ($task->getTaskAssignedTo() === $user) {
            return true;
        }
        
        // Le chef de projet peut accéder à la pièce jointe
        $project = $task->getTaskProject();
        if ($project && $project->getProjectManager() === $user) {
            return true;
        }
        
        // Les utilisateurs avec la permission appropriée peuvent accéder à la pièce jointe
        return $this->permissionService->hasPermission('view_all_attachments');
    }

    /**
     * Récupère des statistiques sur les pièces jointes
     */
    public function getAttachmentStats(): array
    {
        $totalAttachments = $this->attachmentsRepository->count([]);
        $totalSize = $this->attachmentsRepository->getTotalSize();
        
        $stats = [
            'total_count' => $totalAttachments,
            'total_size' => $totalSize,
            'by_type' => [],
        ];
        
        // Statistiques par type de fichier
        $typeStats = $this->attachmentsRepository->getCountByMimeType();
        foreach ($typeStats as $typeStat) {
            $stats['by_type'][$typeStat['mime_type']] = [
                'count' => $typeStat['count'],
                'size' => $typeStat['total_size'],
            ];
        }
        
        return $stats;
    }

    /**
     * Nettoie les pièces jointes orphelines
     */
    public function cleanupOrphanedAttachments(): int
    {
        $orphanedAttachments = $this->attachmentsRepository->findOrphaned();
        $count = count($orphanedAttachments);
        
        foreach ($orphanedAttachments as $attachment) {
            // Supprimer le fichier physique
            $filePath = $this->getAttachmentPath($attachment);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Supprimer l'entité
            $this->entityManager->remove($attachment);
        }
        
        $this->entityManager->flush();
        
        return $count;
    }
} 
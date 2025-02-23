<?php

namespace App\Service;

use App\Entity\Tasks;
use App\Entity\TasksAttachments;
use App\Repository\TasksAttachmentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class TasksAttachmentsService
{
    private string $attachmentsDirectory;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TasksAttachmentsRepository $attachmentsRepository,
        private PermissionService $permissionService,
        private Security $security,
        private SluggerInterface $slugger,
        string $attachmentsDirectory
    ) {
        $this->attachmentsDirectory = $attachmentsDirectory;
    }

    public function uploadAttachment(Tasks $task, UploadedFile $file, ?string $description = null): TasksAttachments
    {
        if (!$this->permissionService->canEditTask($task)) {
            throw new \RuntimeException('Permission denied to add attachment to this task');
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->attachmentsDirectory, $newFilename);

        $attachment = new TasksAttachments();
        $attachment->setTask($task);
        $attachment->setFileName($newFilename);
        $attachment->setOriginalFileName($file->getClientOriginalName());
        $attachment->setFileSize($file->getSize());
        $attachment->setMimeType($file->getMimeType());
        $attachment->setDescription($description);
        $attachment->setUploadedBy($this->security->getUser());

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $attachment;
    }

    public function getAttachmentsByTask(Tasks $task): array
    {
        if (!$this->permissionService->canEditTask($task) && 
            $task->getTaskAssignedTo() !== $this->security->getUser()) {
            throw new \RuntimeException('Permission denied to view task attachments');
        }

        return $this->attachmentsRepository->findByTask($task);
    }

    public function deleteAttachment(int $id): void
    {
        $attachment = $this->attachmentsRepository->find($id);
        if (!$attachment) {
            throw new \RuntimeException('Attachment not found');
        }

        if (!$this->permissionService->canEditTask($attachment->getTask())) {
            throw new \RuntimeException('Permission denied to delete this attachment');
        }

        $filePath = $this->attachmentsDirectory . '/' . $attachment->getFileName();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->entityManager->remove($attachment);
        $this->entityManager->flush();
    }

    public function getRecentAttachments(int $limit = 10): array
    {
        if (!$this->permissionService->hasPermission('view_team_tasks')) {
            throw new \RuntimeException('Permission denied to view recent attachments');
        }

        return $this->attachmentsRepository->findRecentAttachments($limit);
    }

    public function searchAttachments(string $term): array
    {
        if (!$this->permissionService->hasPermission('view_team_tasks')) {
            throw new \RuntimeException('Permission denied to search attachments');
        }

        return $this->attachmentsRepository->searchAttachments([
            'name' => $term
        ]);
    }

    public function getAttachmentPath(TasksAttachments $attachment): string
    {
        if (!$this->permissionService->canEditTask($attachment->getTask()) && 
            $attachment->getTask()->getTaskAssignedTo() !== $this->security->getUser()) {
            throw new \RuntimeException('Permission denied to access this attachment');
        }

        return $this->attachmentsDirectory . '/' . $attachment->getFileName();
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une pièce jointe
     */
    public function canAccessAttachment(TasksAttachments $attachment): bool
    {
        $user = $this->security->getUser();
        if (!$user) {
            return false;
        }

        // L'uploadeur peut toujours accéder à ses pièces jointes
        if ($attachment->getUploadedBy() === $user) {
            return true;
        }

        // Vérifier si l'utilisateur a accès à la tâche associée
        return $this->permissionService->hasPermission('view_team_tasks');
    }

    /**
     * Obtient les statistiques d'utilisation des pièces jointes
     */
    public function getAttachmentStats(): array
    {
        $totalSize = 0;
        $countByType = [];
        $attachments = $this->attachmentsRepository->findAll();

        foreach ($attachments as $attachment) {
            $totalSize += $attachment->getFileSize();
            $type = $attachment->getMimeType();
            $countByType[$type] = ($countByType[$type] ?? 0) + 1;
        }

        return [
            'total_count' => count($attachments),
            'total_size' => $totalSize,
            'by_type' => $countByType
        ];
    }

    /**
     * Nettoie les pièces jointes orphelines (sans tâche associée)
     */
    public function cleanupOrphanedAttachments(): int
    {
        if (!$this->permissionService->hasPermission('manage_tasks')) {
            throw new \RuntimeException('Permission denied to cleanup attachments');
        }

        $orphaned = $this->attachmentsRepository->findOrphanedAttachments();
        $count = count($orphaned);

        foreach ($orphaned as $attachment) {
            // Supprimer le fichier physique
            $filePath = $this->getAttachmentPath($attachment);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $this->entityManager->remove($attachment);
        }

        $this->entityManager->flush();
        return $count;
    }
} 
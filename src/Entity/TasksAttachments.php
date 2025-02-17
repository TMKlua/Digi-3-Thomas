<?php

namespace App\Entity;

use App\Repository\TasksAttachmentsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TasksAttachmentsRepository::class)]
#[ORM\Table(name: 'task_attachments')]
class TasksAttachments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tasks::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'attachment_task_id', referencedColumnName: 'id', nullable: false)]
    private ?Tasks $task = null;

    #[ORM\Column(name: 'attachment_name', length: 255)]
    #[Assert\NotBlank(message: 'Le nom du fichier ne peut pas être vide.')]
    private string $name;

    #[ORM\Column(name: 'attachment_path', length: 255)]
    #[Assert\NotBlank(message: 'Le chemin du fichier ne peut pas être vide.')]
    private string $path;

    #[ORM\Column(name: 'attachment_type', length: 100)]
    private string $type;

    #[ORM\Column(name: 'attachment_size')]
    private int $size;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'attachment_uploaded_by', referencedColumnName: 'id', nullable: false)]
    private ?User $uploadedBy = null;

    #[ORM\Column(name: 'attachment_created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): ?Tasks
    {
        return $this->task;
    }

    public function setTask(?Tasks $task): static
    {
        $this->task = $task;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}

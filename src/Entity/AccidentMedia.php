<?php

namespace App\Entity;

use App\Repository\AccidentMediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccidentMediaRepository::class)]
#[ORM\Table(name: 'accident_media')]
#[ORM\HasLifecycleCallbacks]
class AccidentMedia
{
    public const TYPE_PHOTO = 'PHOTO';
    public const TYPE_DOCUMENT = 'DOCUMENT';
    public const TYPE_CROQUIS = 'CROQUIS';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Accident::class, inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Accident $accident = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccident(): ?Accident
    {
        return $this->accident;
    }

    public function setAccident(?Accident $accident): self
    {
        $this->accident = $accident;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublicPath(): string
    {
        return '/uploads/accidents/' . $this->fileName;
    }

    public function isImage(): bool
    {
        if ($this->mimeType === null) {
            return false;
        }

        return str_starts_with($this->mimeType, 'image/');
    }
}

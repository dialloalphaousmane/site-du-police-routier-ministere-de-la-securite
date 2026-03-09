<?php

namespace App\Entity;

use App\Repository\AccidentVictimRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccidentVictimRepository::class)]
#[ORM\Table(name: 'accident_victim')]
class AccidentVictim
{
    public const TYPE_CONDUCTEUR = 'CONDUCTEUR';
    public const TYPE_PASSAGER = 'PASSAGER';
    public const TYPE_PIETON = 'PIETON';
    public const TYPE_AUTRE = 'AUTRE';

    public const GRAVITY_MORTEL = 'MORTEL';
    public const GRAVITY_BLESSE_GRAVE = 'BLESSE_GRAVE';
    public const GRAVITY_BLESSE_LEGER = 'BLESSE_LEGER';
    public const GRAVITY_INDENNE = 'INDENNE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la victime est obligatoire')]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom de la victime est obligatoire')]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $age = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_CONDUCTEUR, self::TYPE_PASSAGER, self::TYPE_PIETON, self::TYPE_AUTRE])]
    private ?string $typeVictime = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::GRAVITY_MORTEL, self::GRAVITY_BLESSE_GRAVE, self::GRAVITY_BLESSE_LEGER, self::GRAVITY_INDENNE])]
    private ?string $gravite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationalite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $blessures = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(nullable: true)]
    private ?bool $evacue = false;

    #[ORM\Column(nullable: true)]
    private ?bool $decede = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateDeces = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Accident::class, inversedBy: 'victims')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Accident $accident = null;

    #[ORM\ManyToOne(targetEntity: Evacuation::class, inversedBy: 'victims')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Evacuation $evacuation = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getAge(): ?string
    {
        return $this->age;
    }

    public function setAge(?string $age): self
    {
        $this->age = $age;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

    public function getTypeVictime(): ?string
    {
        return $this->typeVictime;
    }

    public function setTypeVictime(string $typeVictime): self
    {
        $this->typeVictime = $typeVictime;
        return $this;
    }

    public function getGravite(): ?string
    {
        return $this->gravite;
    }

    public function setGravite(string $gravite): self
    {
        $this->gravite = $gravite;
        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(?string $nationalite): self
    {
        $this->nationalite = $nationalite;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getBlessures(): ?string
    {
        return $this->blessures;
    }

    public function setBlessures(?string $blessures): self
    {
        $this->blessures = $blessures;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;
        return $this;
    }

    public function isEvacue(): ?bool
    {
        return $this->evacue;
    }

    public function setEvacue(?bool $evacue): self
    {
        $this->evacue = $evacue;
        return $this;
    }

    public function isDecede(): ?bool
    {
        return $this->decede;
    }

    public function setDecede(?bool $decede): self
    {
        $this->decede = $decede;
        return $this;
    }

    public function getDateDeces(): ?\DateTimeImmutable
    {
        return $this->dateDeces;
    }

    public function setDateDeces(?\DateTimeImmutable $dateDeces): self
    {
        $this->dateDeces = $dateDeces;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
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

    public function getEvacuation(): ?Evacuation
    {
        return $this->evacuation;
    }

    public function setEvacuation(?Evacuation $evacuation): self
    {
        $this->evacuation = $evacuation;
        return $this;
    }

    // Méthodes utilitaires
    public function getTypeVictimeLabel(): string
    {
        return match($this->typeVictime) {
            self::TYPE_CONDUCTEUR => '👨‍✈️ Conducteur',
            self::TYPE_PASSAGER => '👥 Passager',
            self::TYPE_PIETON => '🚶 Piéton',
            self::TYPE_AUTRE => '👤 Autre',
            default => 'Inconnu'
        };
    }

    public function getGraviteLabel(): string
    {
        return match($this->gravite) {
            self::GRAVITY_MORTEL => '💀 Mortel',
            self::GRAVITY_BLESSE_GRAVE => '🩹 Blessé grave',
            self::GRAVITY_BLESSE_LEGER => '🤕 Blessé léger',
            self::GRAVITY_INDENNE => '✅ Indemne',
            default => 'Inconnu'
        };
    }

    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function isMortel(): bool
    {
        return $this->gravite === self::GRAVITY_MORTEL || $this->decede;
    }

    public function isBlesse(): bool
    {
        return in_array($this->gravite, [self::GRAVITY_BLESSE_GRAVE, self::GRAVITY_BLESSE_LEGER]);
    }

    public function isGrave(): bool
    {
        return $this->gravite === self::GRAVITY_BLESSE_GRAVE;
    }

    public function getSexeLabel(): string
    {
        return match($this->sexe) {
            'M' => '👨 Masculin',
            'F' => '👩 Féminin',
            default => 'Non spécifié'
        };
    }
}

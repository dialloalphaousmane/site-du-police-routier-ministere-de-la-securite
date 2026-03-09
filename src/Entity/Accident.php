<?php

namespace App\Entity;

use App\Repository\AccidentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccidentRepository::class)]
#[ORM\Table(name: 'accident')]
#[ORM\HasLifecycleCallbacks]
class Accident
{
    public const GRAVITY_MORTEL = 'MORTEL';
    public const GRAVITY_GRAVE = 'GRAVE';
    public const GRAVITY_URGENT = 'URGENT';
    public const GRAVITY_LEGER = 'LEGER';

    public const STATUS_EN_COURS = 'EN_COURS';
    public const STATUS_TRAITE = 'TRAITE';
    public const STATUS_ARCHIVE = 'ARCHIVE';
    public const STATUS_EVACUATION = 'EVACUATION';

    public const CAUSES = [
        'VITESSE_EXCESSIVE' => 'Vitesse excessive',
        'ALCOOL' => 'Conduite sous influence',
        'FATIGUE' => 'Fatigue ou somnolence',
        'EAU_PLOUIE' => 'Route mouillée',
        'MAUVAIS_TEMPS' => 'Mauvais temps',
        'PANNE_MECANIQUE' => 'Panne mécanique',
        'NON_RESPECT_SIGNALISATION' => 'Non respect signalisation',
        'ETAT_ROUTE' => 'Mauvais état de la route',
        'VISIBILITE_REDUITE' => 'Visibilité réduite',
        'AUTRE' => 'Autre'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateAccident = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire')]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commune = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $carrefour = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $meteo = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::GRAVITY_MORTEL, self::GRAVITY_GRAVE, self::GRAVITY_URGENT, self::GRAVITY_LEGER])]
    private ?string $gravite = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_EN_COURS, self::STATUS_TRAITE, self::STATUS_ARCHIVE, self::STATUS_EVACUATION])]
    private ?string $status = self::STATUS_EN_COURS;

    #[ORM\Column]
    private ?int $nbVictimes = 0;

    #[ORM\Column]
    private ?int $nbMorts = 0;

    #[ORM\Column]
    private ?int $nbBlessesGraves = 0;

    #[ORM\Column]
    private ?int $nbBlessesLegers = 0;

    #[ORM\Column(length: 50)]
    private ?string $causePrincipale = null;

    #[ORM\Column(type: 'json')]
    private array $causesSecondaires = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $solutionsProposees = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $mesuresPrevention = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateValidationBrigade = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validatedByBrigade = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validatedBy = null;

    #[ORM\ManyToOne(targetEntity: Brigade::class, inversedBy: 'accidents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brigade $brigade = null;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'accidents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Region $region = null;

    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Agent $agentEnqueteur = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(mappedBy: 'accident', targetEntity: Evacuation::class, cascade: ['persist', 'remove'])]
    private Collection $evacuations;

    #[ORM\OneToMany(mappedBy: 'accident', targetEntity: AccidentVehicle::class, cascade: ['persist', 'remove'])]
    private Collection $vehicles;

    #[ORM\OneToMany(mappedBy: 'accident', targetEntity: AccidentVictim::class, cascade: ['persist', 'remove'])]
    private Collection $victims;

    #[ORM\OneToMany(mappedBy: 'accident', targetEntity: AccidentMedia::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $medias;

    public function __construct()
    {
        $this->evacuations = new ArrayCollection();
        $this->vehicles = new ArrayCollection();
        $this->victims = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->reference = $this->generateReference();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateReference(): string
    {
        return 'ACC-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getDateAccident(): ?\DateTimeImmutable
    {
        return $this->dateAccident;
    }

    public function setDateAccident(\DateTimeImmutable $dateAccident): self
    {
        $this->dateAccident = $dateAccident;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): self
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getCommune(): ?string
    {
        return $this->commune;
    }

    public function setCommune(?string $commune): self
    {
        $this->commune = $commune;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getCarrefour(): ?string
    {
        return $this->carrefour;
    }

    public function setCarrefour(?string $carrefour): self
    {
        $this->carrefour = $carrefour;
        return $this;
    }

    public function getMeteo(): ?string
    {
        return $this->meteo;
    }

    public function setMeteo(?string $meteo): self
    {
        $this->meteo = $meteo;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getNbVictimes(): ?int
    {
        return $this->nbVictimes;
    }

    public function setNbVictimes(int $nbVictimes): self
    {
        $this->nbVictimes = $nbVictimes;
        return $this;
    }

    public function getNbMorts(): ?int
    {
        return $this->nbMorts;
    }

    public function setNbMorts(int $nbMorts): self
    {
        $this->nbMorts = $nbMorts;
        return $this;
    }

    public function getNbBlessesGraves(): ?int
    {
        return $this->nbBlessesGraves;
    }

    public function setNbBlessesGraves(int $nbBlessesGraves): self
    {
        $this->nbBlessesGraves = $nbBlessesGraves;
        return $this;
    }

    public function getNbBlessesLegers(): ?int
    {
        return $this->nbBlessesLegers;
    }

    public function setNbBlessesLegers(int $nbBlessesLegers): self
    {
        $this->nbBlessesLegers = $nbBlessesLegers;
        return $this;
    }

    public function getCausePrincipale(): ?string
    {
        return $this->causePrincipale;
    }

    public function setCausePrincipale(string $causePrincipale): self
    {
        $this->causePrincipale = $causePrincipale;
        return $this;
    }

    public function getCausesSecondaires(): array
    {
        return $this->causesSecondaires;
    }

    public function setCausesSecondaires(array $causesSecondaires): self
    {
        $this->causesSecondaires = $causesSecondaires;
        return $this;
    }

    public function getSolutionsProposees(): ?string
    {
        return $this->solutionsProposees;
    }

    public function setSolutionsProposees(?string $solutionsProposees): self
    {
        $this->solutionsProposees = $solutionsProposees;
        return $this;
    }

    public function getMesuresPrevention(): ?string
    {
        return $this->mesuresPrevention;
    }

    public function setMesuresPrevention(?string $mesuresPrevention): self
    {
        $this->mesuresPrevention = $mesuresPrevention;
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

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeImmutable $dateValidation): self
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getDateValidationBrigade(): ?\DateTimeImmutable
    {
        return $this->dateValidationBrigade;
    }

    public function setDateValidationBrigade(?\DateTimeImmutable $dateValidationBrigade): self
    {
        $this->dateValidationBrigade = $dateValidationBrigade;
        return $this;
    }

    public function getValidatedByBrigade(): ?User
    {
        return $this->validatedByBrigade;
    }

    public function setValidatedByBrigade(?User $validatedByBrigade): self
    {
        $this->validatedByBrigade = $validatedByBrigade;
        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): self
    {
        $this->validatedBy = $validatedBy;
        return $this;
    }

    public function getBrigade(): ?Brigade
    {
        return $this->brigade;
    }

    public function setBrigade(?Brigade $brigade): self
    {
        $this->brigade = $brigade;
        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getAgentEnqueteur(): ?Agent
    {
        return $this->agentEnqueteur;
    }

    public function setAgentEnqueteur(?Agent $agentEnqueteur): self
    {
        $this->agentEnqueteur = $agentEnqueteur;
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

    /**
     * @return Collection<int, Evacuation>
     */
    public function getEvacuations(): Collection
    {
        return $this->evacuations;
    }

    public function addEvacuation(Evacuation $evacuation): self
    {
        if (!$this->evacuations->contains($evacuation)) {
            $this->evacuations->add($evacuation);
            $evacuation->setAccident($this);
        }

        return $this;
    }

    public function removeEvacuation(Evacuation $evacuation): self
    {
        if ($this->evacuations->removeElement($evacuation)) {
            // set the owning side to null (unless already changed)
            if ($evacuation->getAccident() === $this) {
                $evacuation->setAccident(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccidentVehicle>
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(AccidentVehicle $vehicle): self
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
            $vehicle->setAccident($this);
        }

        return $this;
    }

    public function removeVehicle(AccidentVehicle $vehicle): self
    {
        if ($this->vehicles->removeElement($vehicle)) {
            // set the owning side to null (unless already changed)
            if ($vehicle->getAccident() === $this) {
                $vehicle->setAccident(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccidentVictim>
     */
    public function getVictims(): Collection
    {
        return $this->victims;
    }

    public function addVictim(AccidentVictim $victim): self
    {
        if (!$this->victims->contains($victim)) {
            $this->victims->add($victim);
            $victim->setAccident($this);
        }

        return $this;
    }

    public function removeVictim(AccidentVictim $victim): self
    {
        if ($this->victims->removeElement($victim)) {
            // set the owning side to null (unless already changed)
            if ($victim->getAccident() === $this) {
                $victim->setAccident(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AccidentMedia>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(AccidentMedia $media): self
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setAccident($this);
        }

        return $this;
    }

    public function removeMedia(AccidentMedia $media): self
    {
        if ($this->medias->removeElement($media)) {
            if ($media->getAccident() === $this) {
                $media->setAccident(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires
    public function getGraviteLabel(): string
    {
        return match($this->gravite) {
            self::GRAVITY_MORTEL => '🔴 Mortel',
            self::GRAVITY_GRAVE => '🟠 Grave',
            self::GRAVITY_URGENT => '🟡 Urgent',
            self::GRAVITY_LEGER => '🟢 Léger',
            default => 'Inconnu'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_EN_COURS => '🔄 En cours',
            self::STATUS_TRAITE => '✅ Traité',
            self::STATUS_ARCHIVE => '📁 Archivé',
            self::STATUS_EVACUATION => '🚑 Évacuation',
            default => 'Inconnu'
        };
    }

    public function getCausePrincipaleLabel(): string
    {
        return self::CAUSES[$this->causePrincipale] ?? 'Autre';
    }

    public function isUrgent(): bool
    {
        return in_array($this->gravite, [self::GRAVITY_MORTEL, self::GRAVITY_GRAVE, self::GRAVITY_URGENT]);
    }

    public function hasEvacuation(): bool
    {
        return $this->evacuations->count() > 0;
    }

    public function getTotalVictimes(): int
    {
        return $this->nbMorts + $this->nbBlessesGraves + $this->nbBlessesLegers;
    }
}

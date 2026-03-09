<?php

namespace App\Entity;

use App\Repository\EvacuationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvacuationRepository::class)]
#[ORM\Table(name: 'evacuation')]
class Evacuation
{
    public const TYPE_AMBULANCE = 'AMBULANCE';
    public const TYPE_HELI = 'HELI';
    public const TYPE_VEHICULE_PERSONNEL = 'VEHICULE_PERSONNEL';
    public const TYPE_CAMION = 'CAMION';

    public const STATUS_EN_COURS = 'EN_COURS';
    public const STATUS_TERMINE = 'TERMINE';
    public const STATUS_ANNULE = 'ANNULE';

    public const URGENCY_HAUTE = 'HAUTE';
    public const URGENCY_MOYENNE = 'MOYENNE';
    public const URGENCY_BASSE = 'BASSE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateEvacuation = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_AMBULANCE, self::TYPE_HELI, self::TYPE_VEHICULE_PERSONNEL, self::TYPE_CAMION])]
    private ?string $typeEvacuation = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUS_EN_COURS, self::STATUS_TERMINE, self::STATUS_ANNULE])]
    private ?string $status = self::STATUS_EN_COURS;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::URGENCY_HAUTE, self::URGENCY_MOYENNE, self::URGENCY_BASSE])]
    private ?string $urgence = self::URGENCY_MOYENNE;

    #[ORM\Column(length: 255)]
    private ?string $hopitalDestination = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactHopital = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateArrivee = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column]
    private ?int $nbVictimesEvacuees = 0;

    #[ORM\Column]
    private ?int $distanceKm = 0;

    #[ORM\Column]
    private ?int $dureeMinutes = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Accident::class, inversedBy: 'evacuations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Accident $accident = null;

    #[ORM\ManyToOne(targetEntity: Brigade::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brigade $brigade = null;

    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Agent $agentResponsable = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(mappedBy: 'evacuation', targetEntity: AccidentVictim::class)]
    private Collection $victims;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->reference = $this->generateReference();
        $this->dateEvacuation = new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->victims = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateReference(): string
    {
        return 'EVA-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT);
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

    public function getDateEvacuation(): ?\DateTimeImmutable
    {
        return $this->dateEvacuation;
    }

    public function setDateEvacuation(\DateTimeImmutable $dateEvacuation): self
    {
        $this->dateEvacuation = $dateEvacuation;
        return $this;
    }

    public function getTypeEvacuation(): ?string
    {
        return $this->typeEvacuation;
    }

    public function setTypeEvacuation(string $typeEvacuation): self
    {
        $this->typeEvacuation = $typeEvacuation;
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

    public function getUrgence(): ?string
    {
        return $this->urgence;
    }

    public function setUrgence(string $urgence): self
    {
        $this->urgence = $urgence;
        return $this;
    }

    public function getHopitalDestination(): ?string
    {
        return $this->hopitalDestination;
    }

    public function setHopitalDestination(string $hopitalDestination): self
    {
        $this->hopitalDestination = $hopitalDestination;
        return $this;
    }

    public function getContactHopital(): ?string
    {
        return $this->contactHopital;
    }

    public function setContactHopital(?string $contactHopital): self
    {
        $this->contactHopital = $contactHopital;
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

    public function getDateArrivee(): ?\DateTimeImmutable
    {
        return $this->dateArrivee;
    }

    public function setDateArrivee(?\DateTimeImmutable $dateArrivee): self
    {
        $this->dateArrivee = $dateArrivee;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getNbVictimesEvacuees(): ?int
    {
        return $this->nbVictimesEvacuees;
    }

    public function setNbVictimesEvacuees(int $nbVictimesEvacuees): self
    {
        $this->nbVictimesEvacuees = $nbVictimesEvacuees;
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
            $victim->setEvacuation($this);
        }
        return $this;
    }

    public function removeVictim(AccidentVictim $victim): self
    {
        if ($this->victims->removeElement($victim)) {
            if ($victim->getEvacuation() === $this) {
                $victim->setEvacuation(null);
            }
        }
        return $this;
    }

    public function getDistanceKm(): ?int
    {
        return $this->distanceKm;
    }

    public function setDistanceKm(int $distanceKm): self
    {
        $this->distanceKm = $distanceKm;
        return $this;
    }

    public function getDureeMinutes(): ?int
    {
        return $this->dureeMinutes;
    }

    public function setDureeMinutes(int $dureeMinutes): self
    {
        $this->dureeMinutes = $dureeMinutes;
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

    public function getBrigade(): ?Brigade
    {
        return $this->brigade;
    }

    public function setBrigade(?Brigade $brigade): self
    {
        $this->brigade = $brigade;
        return $this;
    }

    public function getAgentResponsable(): ?Agent
    {
        return $this->agentResponsable;
    }

    public function setAgentResponsable(?Agent $agentResponsable): self
    {
        $this->agentResponsable = $agentResponsable;
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

    // Méthodes utilitaires
    public function getTypeEvacuationLabel(): string
    {
        return match($this->typeEvacuation) {
            self::TYPE_AMBULANCE => '🚑 Ambulance',
            self::TYPE_HELI => '🚁 Hélicoptère',
            self::TYPE_VEHICULE_PERSONNEL => '🚗 Véhicule personnel',
            self::TYPE_CAMION => '🚚 Camion',
            default => 'Inconnu'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_EN_COURS => '🔄 En cours',
            self::STATUS_TERMINE => '✅ Terminé',
            self::STATUS_ANNULE => '❌ Annulé',
            default => 'Inconnu'
        };
    }

    public function getUrgenceLabel(): string
    {
        return match($this->urgence) {
            self::URGENCY_HAUTE => '🔴 Haute',
            self::URGENCY_MOYENNE => '🟡 Moyenne',
            self::URGENCY_BASSE => '🟢 Basse',
            default => 'Inconnu'
        };
    }

    public function isEnCours(): bool
    {
        return $this->status === self::STATUS_EN_COURS;
    }

    public function isTermine(): bool
    {
        return $this->status === self::STATUS_TERMINE;
    }

    public function isHauteUrgence(): bool
    {
        return $this->urgence === self::URGENCY_HAUTE;
    }

    public function calculerDuree(): ?int
    {
        if ($this->dateFin && $this->dateEvacuation) {
            return $this->dateFin->getTimestamp() - $this->dateEvacuation->getTimestamp();
        }
        return null;
    }

    public function getDureeFormatee(): string
    {
        $duree = $this->calculerDuree();
        if ($duree === null) {
            return 'N/A';
        }
        
        $minutes = floor($duree / 60);
        $heures = floor($minutes / 60);
        $minutesRestantes = $minutes % 60;
        
        if ($heures > 0) {
            return sprintf('%dh %dmin', $heures, $minutesRestantes);
        }
        return sprintf('%d min', $minutes);
    }
}

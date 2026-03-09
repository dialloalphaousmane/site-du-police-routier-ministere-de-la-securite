<?php

namespace App\Entity;

use App\Repository\AccidentVehicleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccidentVehicleRepository::class)]
#[ORM\Table(name: 'accident_vehicle')]
class AccidentVehicle
{
    public const TYPE_VOITURE = 'VOITURE';
    public const TYPE_MOTO = 'MOTO';
    public const TYPE_CAMION = 'CAMION';
    public const TYPE_BUS = 'BUS';
    public const TYPE_VTU = 'VTU';
    public const TYPE_PL = 'PL';
    public const TYPE_VL = 'VL';
    public const TYPE_AUTRE = 'AUTRE';

    public const DOMMAGE_AUCUN = 'AUCUN';
    public const DOMMAGE_LEGER = 'LEGER';
    public const DOMMAGE_MODERE = 'MODERE';
    public const DOMMAGE_GRAVE = 'GRAVE';
    public const DOMMAGE_DETRUIT = 'DETRUIT';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'L\'immatriculation est obligatoire')]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marque = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modele = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_VOITURE, self::TYPE_MOTO, self::TYPE_CAMION, self::TYPE_BUS, self::TYPE_VTU, self::TYPE_PL, self::TYPE_VL, self::TYPE_AUTRE])]
    private ?string $typeVehicule = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::DOMMAGE_AUCUN, self::DOMMAGE_LEGER, self::DOMMAGE_MODERE, self::DOMMAGE_GRAVE, self::DOMMAGE_DETRUIT])]
    private ?string $niveauDommage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeFabrication = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $proprietaireNom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $proprietairePrenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $proprietaireTelephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assuranceCompagnie = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $assuranceNumero = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $assuranceValidite = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descriptionDommages = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(nullable: true)]
    private ?bool $remorque = false;

    #[ORM\Column(nullable: true)]
    private ?bool $enStationnement = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Accident::class, inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Accident $accident = null;

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

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(?string $marque): self
    {
        $this->marque = $marque;
        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): self
    {
        $this->modele = $modele;
        return $this;
    }

    public function getTypeVehicule(): ?string
    {
        return $this->typeVehicule;
    }

    public function setTypeVehicule(string $typeVehicule): self
    {
        $this->typeVehicule = $typeVehicule;
        return $this;
    }

    public function getNiveauDommage(): ?string
    {
        return $this->niveauDommage;
    }

    public function setNiveauDommage(string $niveauDommage): self
    {
        $this->niveauDommage = $niveauDommage;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getAnneeFabrication(): ?int
    {
        return $this->anneeFabrication;
    }

    public function setAnneeFabrication(?int $anneeFabrication): self
    {
        $this->anneeFabrication = $anneeFabrication;
        return $this;
    }

    public function getProprietaireNom(): ?string
    {
        return $this->proprietaireNom;
    }

    public function setProprietaireNom(?string $proprietaireNom): self
    {
        $this->proprietaireNom = $proprietaireNom;
        return $this;
    }

    public function getProprietairePrenom(): ?string
    {
        return $this->proprietairePrenom;
    }

    public function setProprietairePrenom(?string $proprietairePrenom): self
    {
        $this->proprietairePrenom = $proprietairePrenom;
        return $this;
    }

    public function getProprietaireTelephone(): ?string
    {
        return $this->proprietaireTelephone;
    }

    public function setProprietaireTelephone(?string $proprietaireTelephone): self
    {
        $this->proprietaireTelephone = $proprietaireTelephone;
        return $this;
    }

    public function getAssuranceCompagnie(): ?string
    {
        return $this->assuranceCompagnie;
    }

    public function setAssuranceCompagnie(?string $assuranceCompagnie): self
    {
        $this->assuranceCompagnie = $assuranceCompagnie;
        return $this;
    }

    public function getAssuranceNumero(): ?string
    {
        return $this->assuranceNumero;
    }

    public function setAssuranceNumero(?string $assuranceNumero): self
    {
        $this->assuranceNumero = $assuranceNumero;
        return $this;
    }

    public function getAssuranceValidite(): ?\DateTimeImmutable
    {
        return $this->assuranceValidite;
    }

    public function setAssuranceValidite(?\DateTimeImmutable $assuranceValidite): self
    {
        $this->assuranceValidite = $assuranceValidite;
        return $this;
    }

    public function getDescriptionDommages(): ?string
    {
        return $this->descriptionDommages;
    }

    public function setDescriptionDommages(?string $descriptionDommages): self
    {
        $this->descriptionDommages = $descriptionDommages;
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

    public function isRemorque(): ?bool
    {
        return $this->remorque;
    }

    public function setRemorque(?bool $remorque): self
    {
        $this->remorque = $remorque;
        return $this;
    }

    public function isEnStationnement(): ?bool
    {
        return $this->enStationnement;
    }

    public function setEnStationnement(?bool $enStationnement): self
    {
        $this->enStationnement = $enStationnement;
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

    // Méthodes utilitaires
    public function getTypeVehiculeLabel(): string
    {
        return match($this->typeVehicule) {
            self::TYPE_VOITURE => '🚗 Voiture',
            self::TYPE_MOTO => '🏍️ Moto',
            self::TYPE_CAMION => '🚚 Camion',
            self::TYPE_BUS => '🚌 Bus',
            self::TYPE_VTU => '🚙 Véhicule tourisme',
            self::TYPE_PL => '🚛 Poids lourd',
            self::TYPE_VL => '🚐 Véhicule léger',
            self::TYPE_AUTRE => '🚦 Autre',
            default => 'Inconnu'
        };
    }

    public function getNiveauDommageLabel(): string
    {
        return match($this->niveauDommage) {
            self::DOMMAGE_AUCUN => '✅ Aucun',
            self::DOMMAGE_LEGER => '🟡 Léger',
            self::DOMMAGE_MODERE => '🟠 Modéré',
            self::DOMMAGE_GRAVE => '🔴 Grave',
            self::DOMMAGE_DETRUIT => '💥 Détruit',
            default => 'Inconnu'
        };
    }

    public function getProprietaireNomComplet(): string
    {
        return trim($this->proprietairePrenom . ' ' . $this->proprietaireNom);
    }

    public function getInformationComplete(): string
    {
        $info = $this->immatriculation;
        if ($this->marque) {
            $info .= ' - ' . $this->marque;
        }
        if ($this->modele) {
            $info .= ' ' . $this->modele;
        }
        return $info;
    }

    public function isAssureValide(): bool
    {
        if ($this->assuranceValidite === null) {
            return false;
        }
        return $this->assuranceValidite > new \DateTimeImmutable();
    }

    public function getAgeVehicule(): ?int
    {
        if ($this->anneeFabrication === null) {
            return null;
        }
        return (int)date('Y') - $this->anneeFabrication;
    }
}

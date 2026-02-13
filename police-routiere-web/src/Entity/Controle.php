<?php

namespace App\Entity;

use App\Repository\ControleRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ControleRepository::class)]
class Controle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateControle = null;

    #[ORM\Column(length: 255)]
    private ?string $lieuControle = null;

    #[ORM\Column(length: 255)]
    private ?string $marqueVehicule = null;

    #[ORM\Column(length: 50)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 255)]
    private ?string $nomConducteur = null;

    #[ORM\Column(length: 255)]
    private ?string $prenomConducteur = null;

    #[ORM\Column(length: 50)]
    private ?string $noConducteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $observation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $statut = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $validatedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'controles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agent $agent = null;

    #[ORM\ManyToOne(targetEntity: Brigade::class, inversedBy: 'controles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Brigade $brigade = null;

    #[ORM\OneToMany(mappedBy: 'controle', targetEntity: Infraction::class)]
    private Collection $infractions;

    public function __construct()
    {
        $this->infractions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->dateControle = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateControle(): ?\DateTimeImmutable
    {
        return $this->dateControle;
    }

    public function setDateControle(\DateTimeImmutable $dateControle): static
    {
        $this->dateControle = $dateControle;
        return $this;
    }

    public function getLieuControle(): ?string
    {
        return $this->lieuControle;
    }

    public function setLieuControle(string $lieuControle): static
    {
        $this->lieuControle = $lieuControle;
        return $this;
    }

    public function getMarqueVehicule(): ?string
    {
        return $this->marqueVehicule;
    }

    public function setMarqueVehicule(string $marqueVehicule): static
    {
        $this->marqueVehicule = $marqueVehicule;
        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    public function getNomConducteur(): ?string
    {
        return $this->nomConducteur;
    }

    public function setNomConducteur(string $nomConducteur): static
    {
        $this->nomConducteur = $nomConducteur;
        return $this;
    }

    public function getPrenomConducteur(): ?string
    {
        return $this->prenomConducteur;
    }

    public function setPrenomConducteur(string $prenomConducteur): static
    {
        $this->prenomConducteur = $prenomConducteur;
        return $this;
    }

    public function getNoConducteur(): ?string
    {
        return $this->noConducteur;
    }

    public function setNoConducteur(string $noConducteur): static
    {
        $this->noConducteur = $noConducteur;
        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    public function setAgent(?Agent $agent): static
    {
        $this->agent = $agent;
        return $this;
    }

    public function getBrigade(): ?Brigade
    {
        return $this->brigade;
    }

    public function setBrigade(?Brigade $brigade): static
    {
        $this->brigade = $brigade;
        return $this;
    }

    /**
     * @return Collection<int, Infraction>
     */
    public function getInfractions(): Collection
    {
        return $this->infractions;
    }

    public function addInfraction(Infraction $infraction): static
    {
        if (!$this->infractions->contains($infraction)) {
            $this->infractions->add($infraction);
            $infraction->setControle($this);
        }
        return $this;
    }

    public function removeInfraction(Infraction $infraction): static
    {
        if ($this->infractions->removeElement($infraction)) {
            if ($infraction->getControle() === $this) {
                $infraction->setControle(null);
            }
        }
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): static
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeImmutable $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }
}

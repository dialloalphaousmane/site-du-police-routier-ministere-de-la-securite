<?php

namespace App\Entity;

use App\Repository\BrigadeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BrigadeRepository::class)]
class Brigade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $localite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $chef = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $zoneCouverture = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'brigades')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Region $region = null;

    #[ORM\OneToMany(mappedBy: 'brigade', targetEntity: Agent::class)]
    private Collection $agents;

    #[ORM\OneToMany(mappedBy: 'brigade', targetEntity: Controle::class)]
    private Collection $controles;

    public function __construct()
    {
        $this->agents = new ArrayCollection();
        $this->controles = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLocalite(): ?string
    {
        return $this->localite;
    }

    public function setLocalite(string $localite): static
    {
        $this->localite = $localite;
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

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): static
    {
        $this->region = $region;
        return $this;
    }

    /**
     * @return Collection<int, Agent>
     */
    public function getAgents(): Collection
    {
        return $this->agents;
    }

    public function addAgent(Agent $agent): static
    {
        if (!$this->agents->contains($agent)) {
            $this->agents->add($agent);
            $agent->setBrigade($this);
        }
        return $this;
    }

    public function removeAgent(Agent $agent): static
    {
        if ($this->agents->removeElement($agent)) {
            if ($agent->getBrigade() === $this) {
                $agent->setBrigade(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Controle>
     */
    public function getControles(): Collection
    {
        return $this->controles;
    }

    public function addControle(Controle $controle): static
    {
        if (!$this->controles->contains($controle)) {
            $this->controles->add($controle);
            $controle->setBrigade($this);
        }
        return $this;
    }

    public function removeControle(Controle $controle): static
    {
        if ($this->controles->removeElement($controle)) {
            if ($controle->getBrigade() === $this) {
                $controle->setBrigade(null);
            }
        }
        return $this;
    }

    public function getChef(): ?string
    {
        return $this->chef;
    }

    public function setChef(?string $chef): static
    {
        $this->chef = $chef;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getZoneCouverture(): ?string
    {
        return $this->zoneCouverture;
    }

    public function setZoneCouverture(?string $zoneCouverture): static
    {
        $this->zoneCouverture = $zoneCouverture;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}

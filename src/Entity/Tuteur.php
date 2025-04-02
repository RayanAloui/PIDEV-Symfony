<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TuteurRepository;

#[ORM\Entity(repositoryClass: TuteurRepository::class)]
#[ORM\Table(name: 'tuteurs')]
class Tuteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idT = null;

    public function getIdT(): ?int
    {
        return $this->idT;
    }

    public function setIdT(int $idT): self
    {
        $this->idT = $idT;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $cinT = null;

    public function getCinT(): ?string
    {
        return $this->cinT;
    }

    public function setCinT(string $cinT): self
    {
        $this->cinT = $cinT;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nomT = null;

    public function getNomT(): ?string
    {
        return $this->nomT;
    }

    public function setNomT(string $nomT): self
    {
        $this->nomT = $nomT;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenomT = null;

    public function getPrenomT(): ?string
    {
        return $this->prenomT;
    }

    public function setPrenomT(string $prenomT): self
    {
        $this->prenomT = $prenomT;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telephoneT = null;

    public function getTelephoneT(): ?string
    {
        return $this->telephoneT;
    }

    public function setTelephoneT(?string $telephoneT): self
    {
        $this->telephoneT = $telephoneT;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $adresseT = null;

    public function getAdresseT(): ?string
    {
        return $this->adresseT;
    }

    public function setAdresseT(?string $adresseT): self
    {
        $this->adresseT = $adresseT;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $disponibilite = null;

    public function getDisponibilite(): ?string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(string $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Cour::class, mappedBy: 'tuteur')]
    private Collection $cours;

    /**
     * @return Collection<int, Cour>
     */
    public function getCours(): Collection
    {
        if (!$this->cours instanceof Collection) {
            $this->cours = new ArrayCollection();
        }
        return $this->cours;
    }

    public function addCour(Cour $cour): self
    {
        if (!$this->getCours()->contains($cour)) {
            $this->getCours()->add($cour);
        }
        return $this;
    }

    public function removeCour(Cour $cour): self
    {
        $this->getCours()->removeElement($cour);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Orphelin::class, mappedBy: 'tuteur')]
    private Collection $orphelins;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->orphelins = new ArrayCollection();
    }

    /**
     * @return Collection<int, Orphelin>
     */
    public function getOrphelins(): Collection
    {
        if (!$this->orphelins instanceof Collection) {
            $this->orphelins = new ArrayCollection();
        }
        return $this->orphelins;
    }

    public function addOrphelin(Orphelin $orphelin): self
    {
        if (!$this->getOrphelins()->contains($orphelin)) {
            $this->getOrphelins()->add($orphelin);
        }
        return $this;
    }

    public function removeOrphelin(Orphelin $orphelin): self
    {
        $this->getOrphelins()->removeElement($orphelin);
        return $this;
    }

}

<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\OrphelinRepository;

#[ORM\Entity(repositoryClass: OrphelinRepository::class)]
#[ORM\Table(name: 'orphelins')]
class Orphelin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idO",type: 'integer')]
    private ?int $idO = null;

    public function getIdO(): ?int
    {
        return $this->idO;
    }

    public function setIdO(int $idO): self
    {
        $this->idO = $idO;
        return $this;
    }

    #[ORM\Column(name: "nomO",type: 'string', nullable: false)]
    private ?string $nomO = null;

    public function getNomO(): ?string
    {
        return $this->nomO;
    }

    public function setNomO(string $nomO): self
    {
        $this->nomO = $nomO;
        return $this;
    }

    #[ORM\Column(name: "prenomO",type: 'string', nullable: false)]
    private ?string $prenomO = null;

    public function getPrenomO(): ?string
    {
        return $this->prenomO;
    }

    public function setPrenomO(string $prenomO): self
    {
        $this->prenomO = $prenomO;
        return $this;
    }

    #[ORM\Column(name: "dateNaissance",type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateNaissance = null;

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    #[ORM\Column(name: "sexe",type: 'string', columnDefinition: "ENUM('M', 'F')", options: ["default" => "M"] ,nullable: false)]
    private ?string $sexe = 'M';

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

    #[ORM\Column(name: "situationScolaire",type: 'string', nullable: true)]
    private ?string $situationScolaire = null;

    public function getSituationScolaire(): ?string
    {
        return $this->situationScolaire;
    }

    public function setSituationScolaire(?string $situationScolaire): self
    {
        $this->situationScolaire = $situationScolaire;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Tuteur::class, inversedBy: 'orphelins')]
    #[ORM\JoinColumn(name: 'idTuteur', referencedColumnName: 'idT')]
    private ?Tuteur $tuteur = null;

    public function getTuteur(): ?Tuteur
    {
        return $this->tuteur;
    }

    public function setTuteur(?Tuteur $tuteur): self
    {
        $this->tuteur = $tuteur;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Cour::class, inversedBy: 'orphelins')]
    #[ORM\JoinTable(
        name: 'ratings',
        joinColumns: [
            new ORM\JoinColumn(name: 'id_orphelin', referencedColumnName: 'idO')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'id_cours', referencedColumnName: 'idC')
        ]
    )]
    private Collection $cours;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
    }

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

}

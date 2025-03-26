<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CourRepository;

#[ORM\Entity(repositoryClass: CourRepository::class)]
#[ORM\Table(name: 'cours')]
class Cour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name : 'idC' , type: 'integer')]
    private ?int $idC = null;

    public function getIdC(): ?int
    {
        return $this->idC;
    }

    public function setIdC(int $idC): self
    {
        $this->idC = $idC;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $contenu = null;

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    #[ORM\Column(name: 'imageC', type: 'string', nullable: true)]
    private ?string $imageC = null;

    public function getImageC(): ?string
    {
        return $this->imageC;
    }

    public function setImageC(?string $imageC): self
    {
        $this->imageC = $imageC;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Tuteur::class, inversedBy: 'cours')]
    #[ORM\JoinColumn(name: 'id_t', referencedColumnName: 'idT')]
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

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $note_moyenne = null;

    public function getNote_moyenne(): ?float
    {
        return $this->note_moyenne;
    }

    public function setNote_moyenne(?float $note_moyenne): self
    {
        $this->note_moyenne = $note_moyenne;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resume = null;

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): self
    {
        $this->resume = $resume;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Orphelin::class, inversedBy: 'cours')]
    #[ORM\JoinTable(
        name: 'ratings',
        joinColumns: [
            new ORM\JoinColumn(name: 'id_cours', referencedColumnName: 'idC')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'id_orphelin', referencedColumnName: 'idO')
        ]
    )]
    private Collection $orphelins;

    public function __construct()
    {
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

    public function getNoteMoyenne(): ?float
    {
        return $this->note_moyenne;
    }

    public function setNoteMoyenne(?float $note_moyenne): static
    {
        $this->note_moyenne = $note_moyenne;

        return $this;
    }

}

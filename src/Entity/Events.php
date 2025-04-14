<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Events
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_event;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_event;

    #[ORM\Column(type: "string", length: 150)]
    private string $lieu;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\OneToMany(mappedBy: "id_event", targetEntity: Dons::class)]
    private Collection $dons;

    public function __construct()
    {
        $this->dons = new ArrayCollection();
        $this->date_event = new \DateTime();
    }

    public function getIdEvent(): int
    {
        return $this->id_event;
    }

    public function setIdEvent(int $id_event): self
    {
        $this->id_event = $id_event;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDateEvent(): \DateTimeInterface
    {
        return $this->date_event;
    }

    public function setDateEvent(\DateTimeInterface $date_event): self
    {
        $this->date_event = $date_event;
        return $this;
    }

    public function getLieu(): string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Collection<int, Dons>
     */
    public function getDons(): Collection
    {
        return $this->dons;
    }

    public function addDon(Dons $don): self
    {
        if (!$this->dons->contains($don)) {
            $this->dons->add($don);
            $don->setIdEvent($this);
        }

        return $this;
    }

    public function removeDon(Dons $don): self
    {
        if ($this->dons->removeElement($don)) {
            // set the owning side to null (unless already changed)
            if ($don->getIdEvent() === $this) {
                $don->setIdEvent(null);
            }
        }

        return $this;
    }
    

    
}
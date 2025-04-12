<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActiviteRepository;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $categorie = null;

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $duree = null;

    public function getDuree(): ?string
    {
        return $this->duree;
    }

    public function setDuree(string $duree): self
    {
        $this->duree = $duree;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_du_tuteur = null;

    public function getNom_du_tuteur(): ?string
    {
        return $this->nom_du_tuteur;
    }

    public function setNom_du_tuteur(string $nom_du_tuteur): self
    {
        $this->nom_du_tuteur = $nom_du_tuteur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_activite = null;

    public function getDate_activite(): ?string
    {
        return $this->date_activite;
    }

    public function setDate_activite(string $date_activite): self
    {
        $this->date_activite = $date_activite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $lieu = null;

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNomDuTuteur(): ?string
    {
        return $this->nom_du_tuteur;
    }

    public function setNomDuTuteur(string $nom_du_tuteur): static
    {
        $this->nom_du_tuteur = $nom_du_tuteur;

        return $this;
    }

    public function getDateActivite(): ?string
    {
        return $this->date_activite;
    }

    public function setDateActivite(string $date_activite): static
    {
        $this->date_activite = $date_activite;

        return $this;
    }

}

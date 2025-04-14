<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;


use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User
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
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ'-]+$/", message: "Name must contain only valid characters.")]

    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }









    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ'-]+$/", message: "Name must contain only valid characters.")]
    private ?string $surname = null;

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;
        return $this;
    }









    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: "/^\d{8}$/", message: "Telephone must contain exactly 8 digits.")]
    private ?string $telephone = null;

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }









    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Email(message: "Please enter a valid email address.")]
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








    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\Length(min: 6, minMessage: "Password must be at least 6 characters.")]
    #[Assert\Regex(pattern: "/^(?=.*[A-Za-z])(?=.*\d).{6,}$/", message: "Password must contain letters and numbers.")]
    private ?string $password = null;
    

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }









    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }









    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $isblocked = null;

    public function getIsBlocked(): ?int
    {
        return $this->isblocked;
    }

    public function setIsBlocked(int $isBlocked): self
    {
        $this->isblocked = $isBlocked;
        return $this;
    }










    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $isconfirmed = null;

    public function getIsConfirmed(): ?int
    {
        return $this->isconfirmed;
    }

    public function setIsConfirmed(int $isConfirmed): self
    {
        $this->isconfirmed = $isConfirmed;
        return $this;
    }








    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $numberverification = null;

    public function getNumberVerification(): ?int
    {
        return $this->numberverification;
    }

    public function setNumberVerification(int $numberVerification): self
    {
        $this->numberverification = $numberVerification;
        return $this;
    }










    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $token = null;

    public function getToken(): ?int
    {
        return $this->token;
    }

    public function setToken(int $token): self
    {
        $this->token = $token;
        return $this;
    }









    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

}

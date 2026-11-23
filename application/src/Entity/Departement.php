<?php

namespace App\Entity;

use App\Repository\DepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartementRepository::class)]
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $nom = '';

    /** @var Collection<int, Service> */
    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'departement')]
    private Collection $services;

    public function __construct(string $nom = '')
    {
        $this->nom = $nom;
        $this->services = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /** @return Collection<int, Service> */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}

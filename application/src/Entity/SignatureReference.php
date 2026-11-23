<?php

namespace App\Entity;

use App\Repository\SignatureReferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignatureReferenceRepository::class)]
class SignatureReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'signature')]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $utilisateur;

    #[ORM\Column(type: Types::TEXT)]
    private string $imageChiffree;

    #[ORM\Column(type: Types::TEXT)]
    private string $traitsChiffres;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $essaiRetenu;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creeLe;

    public function __construct(Utilisateur $utilisateur, string $imageChiffree, string $traitsChiffres, int $essaiRetenu)
    {
        $this->utilisateur = $utilisateur;
        $this->imageChiffree = $imageChiffree;
        $this->traitsChiffres = $traitsChiffres;
        $this->essaiRetenu = $essaiRetenu;
        $this->creeLe = new \DateTimeImmutable();
        $utilisateur->setSignature($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getImageChiffree(): string
    {
        return $this->imageChiffree;
    }

    public function getTraitsChiffres(): string
    {
        return $this->traitsChiffres;
    }

    public function getEssaiRetenu(): int
    {
        return $this->essaiRetenu;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }
}

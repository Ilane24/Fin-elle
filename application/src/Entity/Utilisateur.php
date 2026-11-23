<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLES = [
        'ROLE_DEMANDEUR' => 'Demandeur',
        'ROLE_MANAGER' => 'Manager (N+1)',
        'ROLE_RAF' => 'RAF/HF',
        'ROLE_CFO' => 'CFO',
        'ROLE_DG' => 'Direction Générale',
        'ROLE_COMPTA' => 'Comptabilité',
        'ROLE_ADMIN' => 'Administrateur',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private string $matricule = '';

    #[ORM\Column(length: 100)]
    private string $nom = '';

    #[ORM\Column(length: 100)]
    private string $prenom = '';

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 150)]
    private string $fonction = '';

    #[ORM\Column(length: 30)]
    private string $role = 'ROLE_DEMANDEUR';

    #[ORM\Column(length: 255)]
    private string $motDePasse = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\ManyToOne]
    private ?Utilisateur $n1 = null;

    #[ORM\Column]
    private bool $compteMenuesDepenses = false;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $numeroCompteMd = null;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creeLe;

    public function __construct()
    {
        $this->creeLe = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = mb_strtoupper($nom);

        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    public function getInitiales(): string
    {
        return mb_substr($this->prenom, 0, 1).mb_substr($this->nom, 0, 1);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower($email);

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

    public function getFonction(): string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): static
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getRoleLibelle(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER', $this->role];
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasseChiffre): static
    {
        $this->motDePasse = $motDePasseChiffre;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->service?->getDepartement();
    }

    public function getN1(): ?Utilisateur
    {
        return $this->n1;
    }

    public function setN1(?Utilisateur $n1): static
    {
        $this->n1 = $n1;

        return $this;
    }

    public function isN1DuMemeDepartement(): bool
    {
        return $this->n1 !== null && $this->n1->getDepartement() === $this->getDepartement();
    }

    public function hasCompteMenuesDepenses(): bool
    {
        return $this->compteMenuesDepenses;
    }

    public function getNumeroCompteMd(): ?string
    {
        return $this->numeroCompteMd;
    }

    public function ouvrirCompteMenuesDepenses(string $numero): static
    {
        $this->compteMenuesDepenses = true;
        $this->numeroCompteMd = $numero;

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

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }
}

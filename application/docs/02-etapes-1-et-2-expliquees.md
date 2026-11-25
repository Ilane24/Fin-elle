# Étapes 1 et 2 expliquées : la base du projet et la base de données

Ce document reprend **tout le code écrit pour les étapes 1 et 2**, fichier par fichier. Après chaque bloc, vous trouverez l'explication de ce qu'il fait et la raison de ce choix.

Pour installer et lancer le projet, suivez d'abord `01-installation-sur-mac-avec-mamp.md`.

**Sommaire**

1. Comment fonctionne une page Symfony
2. La structure du projet
3. La configuration (`.env`)
4. Les entités : les tables vues depuis PHP
5. Les repositories : chercher dans les tables
6. La migration : le SQL qui crée les tables
7. Les fixtures : les données de démonstration
8. La sécurité : où Symfony trouve les utilisateurs
9. Les contrôleurs : une adresse, une fonction
10. Les gabarits Twig : le HTML
11. Le CSS
12. Le JavaScript
13. Refaire la même chose sur un autre projet

---

## 1. Comment fonctionne une page Symfony

Quand on ouvre <http://localhost:8000/utilisateurs>, voici ce qui se passe :

```
Navigateur ──► public/index.php ──► Symfony cherche la route « /utilisateurs »
                                         │
                                         ▼
                      UtilisateurController::index()      (src/Controller)
                                         │  demande la liste au repository
                                         ▼
                      UtilisateurRepository ──► MySQL     (SELECT … FROM utilisateur)
                                         │  renvoie des objets Utilisateur
                                         ▼
                      utilisateur/index.html.twig         (templates)
                                         │  produit le HTML
                                         ▼
Navigateur ◄──────────────── page HTML + app.css + app.js (public)
```

À retenir : **une route** (l'adresse) mène à **un contrôleur** (la fonction PHP). Ce contrôleur demande les données à **un repository** (la base), puis les donne à **un gabarit Twig** (le HTML).

---

## 2. La structure du projet

```
application/
├── .env                  Réglages (connexion à la base…)
├── composer.json         Liste des bibliothèques utilisées
├── bin/console           L'outil en ligne de commande de Symfony
├── config/               Configuration (sécurité, base de données…)
├── migrations/           Scripts SQL de création des tables
├── public/               Seul dossier visible depuis le navigateur
│   ├── index.php         Point d'entrée de toutes les pages
│   ├── css/app.css       Le style (charte Fin'Elle)
│   ├── js/app.js         Le petit JavaScript
│   └── images/           Le logo
├── src/
│   ├── Controller/       Les contrôleurs (une fonction par page)
│   ├── Entity/           Les entités (une classe par table)
│   ├── Repository/       Les recherches dans la base
│   └── DataFixtures/     Les données de démonstration
├── templates/            Les gabarits Twig (le HTML)
└── docs/                 Ces explications
```

Le dossier `public/` est le seul accessible depuis le navigateur. Le code PHP (`src/`) et la configuration ne sont donc jamais visibles de l'extérieur. C'est une protection importante.

---

## 3. La configuration : `.env`

```dotenv
APP_ENV=dev
DATABASE_URL="mysql://root:root@127.0.0.1:8889/finelle?serverVersion=8.0.32&charset=utf8mb4"
```

- `APP_ENV=dev` : mode développement. Les erreurs sont affichées en détail, avec la barre d'outils de débogage. Sur le serveur de l'entreprise, on passera en `prod`.
- `DATABASE_URL` : l'adresse de la base de données. Elle se lit `mysql://UTILISATEUR:MOT_DE_PASSE@SERVEUR:PORT/NOM_DE_LA_BASE`.
- `utf8mb4` : le jeu de caractères. Il permet de stocker correctement les accents (é, è, ç…).

Si un réglage diffère sur un ordinateur, on ne modifie pas `.env`. On crée un fichier `.env.local`, lu en priorité et jamais envoyé sur GitHub. C'est aussi là qu'on mettra les mots de passe du serveur de production.

---

## 4. Les entités : les tables vues depuis PHP

Une **entité** est une classe PHP qui représente une table. Chaque propriété de la classe correspond à une colonne. On ne manipule plus des lignes SQL, mais des objets : `$utilisateur->getNom()`.

Les lignes qui commencent par `#[ORM\...]` sont des **attributs**. Ce sont des étiquettes que Doctrine, la bibliothèque qui fait le lien avec la base, lit pour savoir comment créer la table.

Les deux tables et leurs liens :

```
departement 1 ──── n utilisateur
                         │ n
                         └──── 1 utilisateur   (le N+1)
```

Un département contient plusieurs utilisateurs. Un utilisateur a un N+1, qui est lui-même un utilisateur.

> **Mise à jour.** Dans la première version, il y avait une table `service` entre le département et l'utilisateur, ainsi qu'une colonne `matricule`. Les deux ont été retirés à votre demande : le département suffit. Le changement est expliqué dans `04-changement-departement-explique.md`. Le code ci-dessous est celui d'aujourd'hui.

### 4.1 `src/Entity/Departement.php`

```php
<?php

namespace App\Entity;

use App\Repository\DepartementRepository;
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

    public function __construct(string $nom = '')
    {
        $this->nom = $nom;
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

    public function __toString(): string
    {
        return $this->nom;
    }
}
```

- `#[ORM\Entity(repositoryClass: ...)]` : cette classe est une table, et ses recherches se font avec `DepartementRepository`.
- `#[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]` sur `$id` : la clé primaire, numérotée automatiquement par MySQL (`AUTO_INCREMENT`).
- `#[ORM\Column(length: 100, unique: true)]` : une colonne `VARCHAR(100)`. Deux départements ne peuvent pas avoir le même nom.
- `__toString()` : permet d'afficher directement un département dans Twig avec `{{ departement }}`.
- Les accesseurs `getNom()` et `setNom()` : les propriétés sont `private`, donc on les lit et on les modifie par ces méthodes. `setNom()` renvoie `$this`, ce qui permet d'enchaîner les appels : `->setNom(...)->setFonction(...)`.

### 4.2 `src/Entity/Utilisateur.php`

```php
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
    private ?Departement $departement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Utilisateur $n1 = null;

    #[ORM\Column]
    private bool $compteMenuesDepenses = false;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $numeroCompteMd = null;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creeLe;

    #[ORM\OneToOne(mappedBy: 'utilisateur')]
    private ?SignatureReference $signature = null;

    public function __construct()
    {
        $this->creeLe = new \DateTimeImmutable();
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

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(Departement $departement): static
    {
        $this->departement = $departement;

        return $this;
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

    public function getSignature(): ?SignatureReference
    {
        return $this->signature;
    }

    public function setSignature(SignatureReference $signature): static
    {
        $this->signature = $signature;

        return $this;
    }
}
```

Les points importants :

- **`implements UserInterface, PasswordAuthenticatedUserInterface`** : on signe un contrat avec le système de sécurité de Symfony. Il exige quatre méthodes, que la connexion de l'étape 3 utilisera :
  - `getUserIdentifier()` renvoie ce qui sert d'identifiant : l'e-mail ;
  - `getPassword()` renvoie le mot de passe **chiffré** ;
  - `getRoles()` renvoie les droits ;
  - `eraseCredentials()` est obligatoire mais vide ici.
- **Les rôles** : la constante `ROLES` liste les rôles possibles avec leur libellé en français. Chaque utilisateur a **un seul rôle**, dans la colonne `role` : c'est plus simple qu'une liste. `getRoles()` y ajoute `ROLE_USER`, que Symfony attend pour toute personne connectée.
- **Le mot de passe** n'est jamais stocké en clair. La méthode s'appelle `setMotDePasse($motDePasseChiffre)` pour rappeler qu'elle reçoit le mot de passe **déjà chiffré** (on le voit dans les fixtures).
- **`$n1`** est une relation `ManyToOne` vers… `Utilisateur` lui-même. Un N+1 est un utilisateur comme un autre. Doctrine crée la colonne `n1_id`. Elle est facultative, car la DG ou le CFO n'ont pas de N+1.
- **`$departement`** est une relation `ManyToOne` : « plusieurs utilisateurs appartiennent à un département ». Doctrine crée la colonne `departement_id`, avec une **clé étrangère** vers `departement.id`. `#[ORM\JoinColumn(nullable: false)]` la rend obligatoire (`NOT NULL`) : tout utilisateur a un département.
- **`isN1DuMemeDepartement()`** applique la règle métier : le N+1 doit appartenir au même département. La page Utilisateurs l'utilise pour signaler un rattachement incorrect.
- **`setNom()`** met le nom en majuscules (`mb_strtoupper`), et **`setEmail()`** met l'e-mail en minuscules. Le préfixe `mb_` indique des fonctions qui gèrent correctement les accents.
- **`ouvrirCompteMenuesDepenses($numero)`** : une méthode qui décrit une action métier plutôt que deux setters séparés. Elle coche l'option et enregistre le numéro de compte en une fois.
- **`creeLe`** est rempli automatiquement dans le constructeur avec la date du jour. `DateTimeImmutable` est une date qu'on ne peut pas modifier par erreur.

---

## 5. Les repositories : chercher dans les tables

```php
<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Utilisateur> */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /** @return Utilisateur[] */
    public function findResponsables(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.actif = true')
            ->andWhere('u.role != :demandeur')
            ->setParameter('demandeur', 'ROLE_DEMANDEUR')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function prochainNumeroCompteMd(): string
    {
        $nombre = $this->count(['compteMenuesDepenses' => true]);

        return sprintf('CMD-%s-%03d', date('Y'), $nombre + 1);
    }
}
```

Les repositories (Departement, Utilisateur, puis SignatureReference à l'étape 3) partent tous de ce modèle. En héritant de `ServiceEntityRepository`, ils reçoivent automatiquement des méthodes de recherche toutes prêtes :

| Méthode | SQL équivalent |
|---|---|
| `find(5)` | `SELECT * FROM utilisateur WHERE id = 5` |
| `findAll()` | `SELECT * FROM utilisateur` |
| `findBy(['actif' => true], ['nom' => 'ASC'])` | `SELECT * FROM utilisateur WHERE actif = 1 ORDER BY nom ASC` |
| `findOneBy(['email' => 'x@y.com'])` | `SELECT * FROM utilisateur WHERE email = 'x@y.com' LIMIT 1` |
| `count(['actif' => true])` | `SELECT COUNT(*) FROM utilisateur WHERE actif = 1` |

Quand on aura besoin de recherches plus complexes (par exemple « les FED en attente de mon N+1 »), on les écrira ici, sous forme de méthodes.

---

## 6. La migration : le SQL qui crée les tables

Une fois les entités écrites, une seule commande compare les entités avec la base et écrit le SQL nécessaire :

```bash
php bin/console make:migration
```

Voici le fichier obtenu (`migrations/Version20261123010100.php`) :

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261123010100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Étape 2 : tables departement, service et utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_C1765B636C6E55B5 (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, libelle VARCHAR(100) NOT NULL, departement_id INT NOT NULL, UNIQUE INDEX UNIQ_E19D9AD277153098 (code), INDEX IDX_E19D9AD2CCF9E01E (departement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, matricule VARCHAR(20) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, fonction VARCHAR(150) NOT NULL, role VARCHAR(30) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, compte_menues_depenses TINYINT NOT NULL, numero_compte_md VARCHAR(20) DEFAULT NULL, actif TINYINT NOT NULL, cree_le DATETIME NOT NULL, service_id INT NOT NULL, n1_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B312B2DC9C (matricule), UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), UNIQUE INDEX UNIQ_1D1C63B3DBBAD5BB (numero_compte_md), INDEX IDX_1D1C63B3ED5CA9E6 (service_id), INDEX IDX_1D1C63B331B7BDD7 (n1_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B331B7BDD7 FOREIGN KEY (n1_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2CCF9E01E');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3ED5CA9E6');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B331B7BDD7');
        $this->addSql('DROP TABLE departement');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE utilisateur');
    }
}
```

Cette première migration crée encore la table `service` et la colonne `matricule`, supprimées ensuite par une autre migration (voir `04-changement-departement-explique.md`). On la garde telle quelle : **une migration déjà exécutée ne se modifie jamais**, on en ajoute une nouvelle.

On y lit du **SQL classique** :

- `CREATE TABLE departement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX ... (nom), PRIMARY KEY (id))` : la table des départements, avec un nom unique.
- Dans `service`, `departement_id INT NOT NULL` et `INDEX ... (departement_id)` : la colonne qui pointe vers le département, avec un index pour que les recherches soient rapides.
- Dans `utilisateur`, `n1_id INT DEFAULT NULL` est facultatif (`DEFAULT NULL`), alors que `service_id INT NOT NULL` est obligatoire.
- Les booléens deviennent des `TINYINT`, qui valent 0 ou 1.
- Les `ALTER TABLE ... ADD CONSTRAINT ... FOREIGN KEY ... REFERENCES ...` créent les **clés étrangères**. MySQL refusera, par exemple, un utilisateur rattaché à un service qui n'existe pas.
- `up()` contient ce qu'on applique. `down()` contient ce qui permet de revenir en arrière.

Puis on exécute le script :

```bash
php bin/console doctrine:migrations:migrate
```

Doctrine note dans une table `doctrine_migration_versions` les migrations déjà exécutées. Chaque script ne s'exécute donc qu'une fois. Aux étapes suivantes, de nouvelles migrations ajouteront de nouvelles tables (demandes, signatures…) sans toucher aux données existantes.

---

## 7. Les fixtures : les données de démonstration

```php
<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEPARTEMENTS = [
        'ADMINISTRATION & LOGISTIQUE',
        'FINANCE & COMPTABILITÉ',
        'DIRECTION GÉNÉRALE',
        'RESSOURCES HUMAINES',
        "SYSTÈMES D'INFORMATION",
        'COMMERCIAL',
    ];

    private const UTILISATEURS = [
        ['mireille.gbagbo', 'Mireille', 'Gbagbo', 'Directrice administrative', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_ADMIN', null, null],
        ['regis.kouame', 'Régis', 'Kouamé', 'Directeur Financier et Comptable', 'FINANCE & COMPTABILITÉ', 'ROLE_CFO', null, null],
        ['mariam.bamba', 'Mariam Yenita', 'Bamba', 'Directrice Générale', 'DIRECTION GÉNÉRALE', 'ROLE_DG', null, null],
        ['aminata.kone', 'Aminata', 'Koné', 'Responsable Ressources Humaines', 'RESSOURCES HUMAINES', 'ROLE_MANAGER', null, null],
        ['yves.brou', 'Yves', 'Brou', 'Responsable Informatique', "SYSTÈMES D'INFORMATION", 'ROLE_MANAGER', null, null],
        ['nadia.cisse', 'Nadia', 'Cissé', 'Directrice Commerciale', 'COMMERCIAL', 'ROLE_MANAGER', null, null],
        ['serge.kouadio', 'Serge', 'Kouadio', 'Responsable Facilities', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_MANAGER', 'mireille.gbagbo', 'CMD-2026-003'],
        ['carine.toro', 'Carine', 'Toro', 'Responsable Achats et Moyens Généraux', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_RAF', 'mireille.gbagbo', null],
        ['paul.yao', 'Paul', 'Yao', 'Comptable', 'FINANCE & COMPTABILITÉ', 'ROLE_COMPTA', 'regis.kouame', null],
        ['fatou.diallo', 'Fatou', 'Diallo', 'Assistante Facilities', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', 'CMD-2026-001'],
        ['awa.ndiaye', 'Awa', 'Ndiaye', 'Technicienne maintenance', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', null],
        ['ibrahim.toure', 'Ibrahim', 'Touré', 'Chef de la sécurité', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', 'CMD-2026-002'],
        ['koffi.nguessan', 'Koffi', "N'Guessan", 'Agent logistique (achats)', 'ADMINISTRATION & LOGISTIQUE', 'ROLE_DEMANDEUR', 'serge.kouadio', null],
        ['eric.konan', 'Éric', 'Konan', 'Chargé de clientèle', 'COMMERCIAL', 'ROLE_DEMANDEUR', 'nadia.cisse', null],
    ];

    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $departements = [];
        foreach (self::DEPARTEMENTS as $nom) {
            $departements[$nom] = new Departement($nom);
            $manager->persist($departements[$nom]);
        }

        $utilisateurs = [];
        foreach (self::UTILISATEURS as [$identifiant, $prenom, $nom, $fonction, $departement, $role, $n1, $compteMd]) {
            $utilisateur = new Utilisateur();
            $utilisateur
                ->setPrenom($prenom)
                ->setNom($nom)
                ->setEmail($identifiant.'@cofina-demo.com')
                ->setFonction($fonction)
                ->setDepartement($departements[$departement])
                ->setRole($role)
                ->setN1($n1 ? $utilisateurs[$n1] : null)
                ->setMotDePasse($this->hasher->hashPassword($utilisateur, 'demo2026'));
            if ($compteMd) {
                $utilisateur->ouvrirCompteMenuesDepenses($compteMd);
            }
            $manager->persist($utilisateur);
            $utilisateurs[$identifiant] = $utilisateur;
        }

        $manager->flush();
    }
}
```

- Les deux constantes `DEPARTEMENTS` et `UTILISATEURS` décrivent les données sous forme de tableaux PHP. C'est facile à lire et à modifier.
- `public function __construct(private UserPasswordHasherInterface $hasher)` : c'est l'**injection de dépendances**. On déclare dans le constructeur l'outil dont on a besoin (le chiffreur de mots de passe), et Symfony le fournit tout seul. Le mot-clé `private` dans le constructeur crée directement la propriété `$this->hasher`.
- `$manager->persist($objet)` dit à Doctrine : « cet objet devra être enregistré ». Rien n'est encore écrit dans la base.
- `$manager->flush()`, à la fin, écrit tout d'un coup : Doctrine génère et exécute tous les `INSERT INTO ...`.
- `foreach (... as [$identifiant, $prenom, ...])` : la **déstructuration**. Chaque ligne du tableau est découpée en variables nommées.
- `$departements[$departement]` : les départements sont d'abord créés et rangés dans un tableau, par nom. On les retrouve ensuite par leur nom pour les rattacher aux utilisateurs.
- L'ordre des utilisateurs compte : un N+1 doit être créé **avant** ses collaborateurs. Ainsi, `$utilisateurs[$n1]` existe déjà quand on le rattache.
- `hashPassword($utilisateur, 'demo2026')` chiffre le mot de passe. La base ne contient qu'un texte illisible du type `$2y$13$...` : même avec un accès à la base, on ne peut pas retrouver le mot de passe.

Commande : `php bin/console doctrine:fixtures:load`. **Attention : elle vide d'abord toutes les tables.** Elle ne sert qu'en développement.

---

## 8. La sécurité : où Symfony trouve les utilisateurs

Extrait de `config/packages/security.yaml` :

```yaml
security:
    # https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    # https://symfony.com/doc/current/security.html#loading-the-user-the-user-provider
    providers:
        utilisateurs:
            entity:
                class: App\Entity\Utilisateur
                property: email

    firewalls:
        dev:
            # Ensure dev tools and static assets are always allowed
            pattern: ^/(_profiler(/|$)|(_wdt|assets|build)/)
            security: false
        main:
            lazy: true
            provider: utilisateurs
```

- `password_hashers ... 'auto'` : Symfony choisit le meilleur algorithme de chiffrement disponible (bcrypt ou argon2).
- `providers` : quand quelqu'un se connectera, Symfony cherchera dans l'entité `Utilisateur` une ligne dont l'`email` correspond.
- `firewalls` : le « pare-feu » qui protège les pages. On le complétera à l'étape 3 avec le formulaire de connexion, puis on réservera les pages aux personnes connectées.

---

## 9. Les contrôleurs : une adresse, une fonction

### `src/Controller/AccueilController.php`

```php
<?php

namespace App\Controller;

use App\Repository\DepartementRepository;
use App\Repository\SignatureReferenceRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'accueil')]
    public function index(
        UtilisateurRepository $utilisateurs,
        DepartementRepository $departements,
        SignatureReferenceRepository $signatures,
    ): Response {
        return $this->render('accueil/index.html.twig', [
            'nbUtilisateurs' => $utilisateurs->count(['actif' => true]),
            'nbDepartements' => $departements->count([]),
            'nbSignatures' => $signatures->count([]),
            'nbComptesMd' => $utilisateurs->count(['compteMenuesDepenses' => true]),
        ]);
    }
}
```

- `#[Route('/', name: 'accueil')]` : cette fonction répond à l'adresse `/`. Le nom `accueil` sert à fabriquer les liens dans Twig, avec `path('accueil')`. Si l'adresse change un jour, les liens suivent automatiquement.
- Les paramètres `UtilisateurRepository $utilisateurs` et suivants : là encore, Symfony fournit les objets tout seuls (injection de dépendances).
- `$this->render('gabarit', [données])` exécute le gabarit Twig en lui transmettant des variables, puis renvoie la page HTML.

### `src/Controller/UtilisateurController.php`

```php
<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UtilisateurController extends AbstractController
{
    #[Route('/utilisateurs', name: 'utilisateurs')]
    public function index(UtilisateurRepository $utilisateurs): Response
    {
        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurs->findBy([], ['nom' => 'ASC']),
        ]);
    }
}
```

`findBy([], ['nom' => 'ASC'])` signifie « tous les utilisateurs, triés par nom ».

---

## 10. Les gabarits Twig : le HTML

Twig, c'est du HTML avec trois syntaxes en plus :

| Syntaxe | Rôle | Exemple |
|---|---|---|
| `{{ ... }}` | **Afficher** une valeur | `{{ u.nomComplet }}` |
| `{% ... %}` | **Faire** quelque chose (boucle, condition, bloc) | `{% for u in utilisateurs %}` |
| `{# ... #}` | Commentaire | |

Twig **échappe automatiquement** ce qu'il affiche : un nom contenant `<script>` s'affichera comme du texte et ne sera pas exécuté. C'est une protection contre les attaques par injection de code.

### `templates/base.html.twig` : la mise en page commune

```twig
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{% block title %}Fin'Elle{% endblock %} · Fin'Elle</title>
    <link rel="icon" href="{{ asset('images/logo-finelle.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    {% block javascripts %}{% endblock %}
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <a href="{{ path('accueil') }}" class="logo"><img src="{{ asset('images/logo-finelle.png') }}" alt="Fin'Elle – Filiale du Groupe Cofina"></a>
        <nav class="nav">
            <span class="nav-label">Dépenses</span>
            <a href="{{ path('accueil') }}" class="nav-item {{ app.current_route == 'accueil' ? 'active' }}">Tableau de bord</a>
            <span class="nav-label">Mon compte</span>
            <a href="{{ path('signature') }}" class="nav-item {{ app.current_route == 'signature' ? 'active' }}">Ma signature</a>
            {% if is_granted('ROLE_ADMIN') %}
                <span class="nav-label">Administration</span>
                <a href="{{ path('utilisateurs') }}" class="nav-item {{ app.current_route == 'utilisateurs' ? 'active' }}">Utilisateurs</a>
            {% endif %}
        </nav>
        <div class="sidebar-foot">
            <div class="user-chip">
                <span class="avatar">{{ app.user.initiales }}</span>
                <div class="meta">
                    <b>{{ app.user.nomComplet }}</b>
                    <span>{{ app.user.roleLibelle }} · {{ app.user.departement }}</span>
                </div>
                <a href="{{ path('deconnexion') }}" class="lien-deconnexion" title="Se déconnecter">Quitter</a>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <span class="muted">Circuit des dépenses · Fin'Elle</span>
        </header>

        <main class="content">
            {% for type, messages in app.flashes %}
                {% for message in messages %}
                    <div class="alert alert-{{ type }}" data-auto-fermeture>{{ message }}</div>
                {% endfor %}
            {% endfor %}

            {% block body %}{% endblock %}
        </main>
    </div>
</div>
</body>
</html>
```

- C'est le squelette commun à toutes les pages : la barre latérale avec le logo et le menu, l'en-tête, puis la zone de contenu.
- `{% block body %}{% endblock %}` est un **emplacement vide**. Chaque page le remplit avec son propre contenu.
- `asset('css/app.css')` construit le bon lien vers un fichier du dossier `public/`.
- `path('utilisateurs')` construit le lien vers la route qui porte ce nom.
- `app.current_route == 'accueil' ? 'active'` ajoute la classe `active` au lien de la page en cours, ce qui le surligne en rouge dans le menu.
- `app.flashes` affiche les **messages flash** : les messages laissés par un contrôleur après une action, par exemple « Demande envoyée ». Chacun s'affiche une seule fois, sur la page suivante. L'attribut `data-auto-fermeture` permet au JavaScript de le faire disparaître (voir la section 12).
- `<script ... defer>` : le script est chargé sans bloquer l'affichage de la page.

### `templates/accueil/index.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Tableau de bord{% endblock %}

{% block body %}
    <div class="page-head">
        <div>
            <h1>Tableau de bord</h1>
            <p class="muted">Bienvenue sur l'application de gestion des achats Fin'Elle.</p>
        </div>
    </div>

    <div class="kpis">
        <div class="card kpi">
            <span class="kpi-top">Utilisateurs actifs</span>
            <span class="kpi-val">{{ nbUtilisateurs }}</span>
        </div>
        <div class="card kpi">
            <span class="kpi-top">Départements</span>
            <span class="kpi-val">{{ nbDepartements }}</span>
        </div>
        <div class="card kpi">
            <span class="kpi-top">Signatures enregistrées</span>
            <span class="kpi-val">{{ nbSignatures }}</span>
        </div>
        <div class="card kpi">
            <span class="kpi-top">Comptes menues dépenses</span>
            <span class="kpi-val">{{ nbComptesMd }}</span>
        </div>
    </div>
{% endblock %}
```

- `{% extends 'base.html.twig' %}` : « je reprends la mise en page commune ».
- `{% block body %}` : « voici ce que je mets dans l'emplacement `body` ».
- `{{ nbUtilisateurs }}` affiche la variable envoyée par le contrôleur.

### `templates/utilisateur/index.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Utilisateurs{% endblock %}

{% block body %}
    <div class="page-head">
        <div>
            <h1>Utilisateurs</h1>
            <p class="muted">Chaque utilisateur appartient à un département. Son N+1, qui signe ses FED, appartient au même département.</p>
        </div>
        <input type="search" class="input recherche" placeholder="Rechercher un nom, un département…" data-filtre-tableau="#tableUtilisateurs">
    </div>

    <div class="table-wrap">
        <table id="tableUtilisateurs">
            <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Fonction</th>
                <th>Département</th>
                <th>Rôle</th>
                <th>N+1</th>
                <th>Menues dépenses</th>
                <th>Statut</th>
            </tr>
            </thead>
            <tbody>
            {% for u in utilisateurs %}
                <tr>
                    <td>
                        <div class="who">
                            <span class="avatar">{{ u.initiales }}</span>
                            <div><b>{{ u.nomComplet }}</b><div class="muted petit">{{ u.email }}</div></div>
                        </div>
                    </td>
                    <td>{{ u.fonction }}</td>
                    <td>{{ u.departement }}</td>
                    <td>{{ u.roleLibelle }}</td>
                    <td>
                        {% if u.n1 %}
                            {{ u.n1.nomComplet }}
                            {% if not u.n1DuMemeDepartement %}<div><span class="badge b-danger">Autre département</span></div>{% endif %}
                        {% else %}
                            <span class="muted">—</span>
                        {% endif %}
                    </td>
                    <td>{{ u.compteMenuesDepenses ? u.numeroCompteMd : '—' }}</td>
                    <td>
                        {% if u.actif %}<span class="badge b-success">Actif</span>{% else %}<span class="badge b-neutral">Désactivé</span>{% endif %}
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    </div>
{% endblock %}
```

- `{% for u in utilisateurs %} ... {% endfor %}` : une ligne du tableau par utilisateur.
- `{{ u.nomComplet }}` : Twig appelle tout seul `$u->getNomComplet()`. De même, `u.departement` appelle `getDepartement()`, et `u.n1DuMemeDepartement` appelle `isN1DuMemeDepartement()`. On écrit le nom sans `get`, `is` ou `has`.
- `{% if u.n1 %} ... {% else %} ... {% endif %}` : une condition. Si l'utilisateur n'a pas de N+1, on affiche un tiret.
- `{{ u.compteMenuesDepenses ? u.numeroCompteMd : '—' }}` : une condition courte (`condition ? si vrai : si faux`).
- L'attribut `data-filtre-tableau="#tableUtilisateurs"` du champ de recherche indique au JavaScript quel tableau filtrer.

---

## 11. Le CSS : `public/css/app.css`

```css
:root {
  --accent: #DC092F;
  --accent-hover: #B80727;
  --accent-soft: #FDE8EC;
  --accent-ring: rgba(220, 9, 47, .22);

  --bg: #FFFFFF;
  --surface: #F5F6F8;
  --surface-2: #ECEEF1;
  --border: #E1E4E8;
  --text: #2F3437;
  --text-muted: #646363;

  --success: #2F9E62;  --success-soft: #E6F4EC;
  --warning: #C77700;  --warning-soft: #FFF3DF;
  --danger:  #A8201A;  --danger-soft:  #FBEAE8;
  --info:    #2C6DB5;  --info-soft:    #E7F0FA;

  --radius-sm: 8px;
  --radius: 12px;
  --shadow-sm: 0 1px 2px rgba(16, 24, 40, .06);
  --shadow-hover: 0 8px 20px rgba(16, 24, 40, .10);
  --t: 200ms;
  --sidebar-w: 248px;

  font-family: "Segoe UI", system-ui, -apple-system, Roboto, "Helvetica Neue", Arial, sans-serif;
}

* { box-sizing: border-box; }
html, body { margin: 0; height: 100%; }
body { background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.5; }
a { color: var(--accent); text-decoration: none; }
h1, h2, h3 { margin: 0; font-weight: 600; }
h1 { font-size: 22px; }
h2 { font-size: 16px; }
.muted { color: var(--text-muted); }
.petit { font-size: 12px; }

/* Mise en page : barre latérale à gauche, contenu à droite */
.app { display: grid; grid-template-columns: var(--sidebar-w) 1fr; min-height: 100%; }
.sidebar {
  position: sticky; top: 0; height: 100vh; padding: 18px 14px;
  display: flex; flex-direction: column; gap: 22px;
  background: var(--bg); border-right: 1px solid var(--border);
}
.logo img { height: 46px; }
.nav { display: grid; gap: 2px; }
.nav-label { padding: 10px 12px 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
.nav-item { padding: 9px 12px; border-radius: var(--radius-sm); color: var(--text); font-weight: 500; transition: background var(--t); }
.nav-item:hover { background: var(--surface); }
.nav-item.active { background: var(--accent-soft); color: var(--accent); font-weight: 600; }

.main { min-width: 0; display: flex; flex-direction: column; }
.topbar { position: sticky; top: 0; z-index: 10; padding: 14px 28px; background: rgba(255, 255, 255, .9); border-bottom: 1px solid var(--border); }
.content { padding: 28px; display: grid; gap: 24px; max-width: 1280px; width: 100%; }
.page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.page-head p { margin: 4px 0 0; }

/* Cartes et indicateurs */
.card { padding: 20px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); transition: transform var(--t), box-shadow var(--t); }
.card:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
.kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.kpi { display: grid; gap: 6px; }
.kpi-top { color: var(--text-muted); font-size: 13px; font-weight: 500; }
.kpi-val { font-size: 26px; font-weight: 700; }

/* Boutons */
.btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border: 1px solid transparent; border-radius: var(--radius-sm); font: inherit; font-weight: 600; cursor: pointer; transition: background var(--t), transform var(--t); }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
.btn-ghost { background: var(--bg); color: var(--text); border-color: var(--border); }
.btn-ghost:hover { border-color: var(--accent); color: var(--accent); }

/* Champs de formulaire */
.input { width: 100%; padding: 10px 12px; font: inherit; color: var(--text); background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); }
.input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-ring); }
.recherche { max-width: 320px; }

/* Tableaux */
.table-wrap { overflow-x: auto; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 16px; text-align: left; white-space: nowrap; }
th { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); background: var(--surface); border-bottom: 1px solid var(--border); }
td { border-bottom: 1px solid var(--border); }
tbody tr:last-child td { border-bottom: 0; }
tbody tr:hover td { background: #FCFCFD; }
.who { display: flex; align-items: center; gap: 10px; }
.avatar { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; flex-shrink: 0; background: var(--accent-soft); color: var(--accent); font-weight: 700; font-size: 12px; }

/* Pastilles de statut */
.badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
.badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.b-success { background: var(--success-soft); color: var(--success); }
.b-warning { background: var(--warning-soft); color: var(--warning); }
.b-danger { background: var(--danger-soft); color: var(--danger); }
.b-info { background: var(--info-soft); color: var(--info); }
.b-neutral { background: var(--surface-2); color: var(--text-muted); }

/* Messages (après une action) */
.alert { padding: 14px 16px; border: 1px solid; border-radius: var(--radius); transition: opacity .4s; }
.alert-success { background: var(--success-soft); border-color: #BFE3CD; color: var(--success); }
.alert-error { background: var(--danger-soft); border-color: #F5C2C2; color: var(--danger); }
.alert.disparait { opacity: 0; }

/* Barre latérale : personne connectée */
.sidebar-foot { margin-top: auto; }
.user-chip { display: flex; align-items: center; gap: 10px; padding: 10px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.user-chip .meta { min-width: 0; flex: 1; }
.user-chip .meta b, .user-chip .meta span { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-chip .meta span { font-size: 12px; color: var(--text-muted); }
.lien-deconnexion { font-size: 12px; font-weight: 600; }

/* Pages simples : connexion, création de compte, signature */
.page-simple { display: grid; place-items: center; min-height: 100%; padding: 32px 16px; background: radial-gradient(1200px 600px at 100% 0, var(--accent-soft), transparent 60%), var(--bg); }
.carte-simple { width: 100%; max-width: 440px; padding: 32px; display: grid; gap: 16px; background: var(--bg); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-hover); }
.carte-simple.carte-large { max-width: 880px; }
.carte-simple p { margin: 0; }
.logo-grand { height: 58px; }
.bas-de-carte { text-align: center; color: var(--text-muted); }

/* Formulaires */
.formulaire { display: grid; gap: 14px; }
.champ { display: grid; gap: 6px; align-content: start; }
.champ label { font-size: 13px; font-weight: 600; }
.grille-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 16px; }
.pleine-largeur { grid-column: 1 / -1; }
.partie { margin-top: 6px; padding-bottom: 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); border-bottom: 1px solid var(--border); }
.erreur { font-size: 12.5px; color: var(--danger); }
.aide { font-size: 12px; color: var(--text-muted); }
.option { display: flex; gap: 12px; padding: 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; }
.option input { accent-color: var(--accent); width: 18px; height: 18px; }
.btn-large { justify-content: center; padding: 12px 16px; }
.btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }
select.input:disabled { background: var(--surface); }

/* Enregistrement de la signature */
.etapes { display: flex; gap: 8px; margin: 0; padding: 0; list-style: none; counter-reset: etape; }
.etapes li { flex: 1; padding: 8px 12px; text-align: center; font-size: 13px; color: var(--text-muted); background: var(--surface); border: 1px solid var(--border); border-radius: 999px; }
.etapes li.en-cours { color: var(--accent); font-weight: 600; background: var(--accent-soft); border-color: var(--accent); }
.etapes li.fait { color: var(--success); background: var(--success-soft); border-color: #BFE3CD; }
.consignes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.carte-consigne { display: grid; gap: 2px; padding: 12px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.carte-consigne span { font-size: 12.5px; color: var(--text-muted); }
.zone-essai { display: grid; grid-template-columns: 1fr 180px; gap: 20px; align-items: start; }
.zone-essai h2 { margin-bottom: 10px; }
.pad { display: block; width: 100%; height: 220px; background: var(--bg); border: 1.5px dashed var(--accent); border-radius: var(--radius-sm); cursor: crosshair; touch-action: none; }
.sous-pad { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
.vignettes { display: grid; gap: 10px; }
.vignettes h3 { font-size: 12.5px; }
.vignette { display: grid; gap: 4px; min-height: 70px; padding: 8px; text-align: left; font: inherit; font-size: 12px; color: var(--text-muted); background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: border-color var(--t); }
.vignette:hover:not(:disabled) { border-color: var(--accent); }
.vignette:disabled { cursor: default; }
.vignette.en-cours { color: var(--accent); border: 1px dashed var(--accent); }
.vignette img { max-width: 100%; max-height: 44px; }
.actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.signature-carte { display: grid; gap: 12px; max-width: 520px; }
.signature-image { display: grid; place-items: center; height: 160px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); }
.signature-image img { max-height: 140px; max-width: 100%; }
```

- **Les variables** (`--accent`, `--surface`…) dans `:root` regroupent les couleurs de la charte. Elles sont reprises de la maquette. Pour changer le rouge Fin'Elle partout, il suffit de modifier une seule ligne. On les utilise avec `var(--accent)`.
- **La mise en page** utilise CSS Grid : `.app { grid-template-columns: var(--sidebar-w) 1fr; }` crée deux colonnes, la barre latérale de 248 px et le reste de la largeur (`1fr`).
- `position: sticky; top: 0;` garde la barre latérale et l'en-tête visibles quand on fait défiler la page.
- **Les micro-animations** : `transition` rend les changements progressifs. Au survol (`:hover`), les cartes montent légèrement (`translateY(-2px)`) avec une ombre plus marquée.
- **Les pastilles** (`.badge`) affichent un petit rond de couleur avant le texte, grâce à `::before`. Ce rond prend la couleur du texte (`currentColor`).
- `.kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }` crée 4 colonnes de même largeur pour les indicateurs.

---

## 12. Le JavaScript : `public/js/app.js`

```javascript
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-fermeture]').forEach((message) => {
        setTimeout(() => {
            message.classList.add('disparait');
            setTimeout(() => message.remove(), 400);
        }, 5000);
    });

    document.querySelectorAll('[data-filtre-tableau]').forEach((champ) => {
        const tableau = document.querySelector(champ.dataset.filtreTableau);
        champ.addEventListener('input', () => {
            const texte = champ.value.toLowerCase();
            tableau.querySelectorAll('tbody tr').forEach((ligne) => {
                const correspond = ligne.textContent.toLowerCase().includes(texte);
                ligne.style.display = correspond ? '' : 'none';
            });
        });
    });
});
```

Ce fichier contient **deux petites fonctions**, qui démarrent quand la page est chargée (`DOMContentLoaded`).

**1. Faire disparaître les messages tout seuls**

- `document.querySelectorAll('[data-auto-fermeture]')` cherche tous les éléments qui portent l'attribut `data-auto-fermeture` : les messages flash du gabarit de base.
- `setTimeout(..., 5000)` attend 5 secondes, puis ajoute la classe `disparait`. Le CSS rend alors le message transparent en 0,4 seconde (`opacity: 0`).
- Un second `setTimeout(..., 400)` retire ensuite le message de la page.
- **Sans ce script**, le message resterait affiché jusqu'au prochain changement de page. Rien ne serait cassé : le script améliore seulement le confort.

**2. Filtrer un tableau pendant la saisie**

- Le script cherche les champs qui portent `data-filtre-tableau`. La valeur de l'attribut, ici `#tableUtilisateurs`, désigne le tableau à filtrer.
- `champ.addEventListener('input', ...)` déclenche la fonction à **chaque lettre tapée**.
- Pour chaque ligne (`tbody tr`), on regarde si son texte contient ce qui a été tapé (`includes`), en ignorant les majuscules (`toLowerCase`).
- On masque les lignes qui ne correspondent pas (`style.display = 'none'`) et on réaffiche les autres (`''`).
- Tout se passe dans le navigateur : **aucune requête n'est envoyée au serveur**, et les données ne changent pas. C'est un simple affichage.
- Le même attribut, posé sur n'importe quel autre champ, filtrera n'importe quel autre tableau. Le script est réutilisable sans modification.

**Pourquoi des attributs `data-...` ?** Le HTML indique **quoi** faire (« ce champ filtre ce tableau »), et le JavaScript sait **comment** le faire. On n'écrit pas de JavaScript dans le HTML, et un même script sert à toutes les pages.

---

## 13. Refaire la même chose sur un autre projet

```bash
# 1. Créer un projet Symfony
composer create-project symfony/skeleton:"7.4.*" mon-projet
cd mon-projet

# 2. Ajouter les briques utilisées ici
composer require twig asset symfony/orm-pack security
composer require --dev symfony/maker-bundle orm-fixtures

# 3. Régler DATABASE_URL dans .env, puis créer la base
php bin/console doctrine:database:create

# 4. Créer une entité (questions posées dans le Terminal)
php bin/console make:entity

# 5. Générer puis exécuter le SQL
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 6. Créer un contrôleur et son gabarit
php bin/console make:controller

# 7. Lancer le site
php -S localhost:8000 -t public
```

`make:entity` et `make:controller` génèrent le code de départ en posant des questions dans le Terminal (nom de la colonne, type, longueur…). Il ne reste plus qu'à l'adapter. C'est exactement ce que j'ai fait pour ce projet, en simplifiant ensuite le code généré.

---

**Étape suivante (3)** : la connexion, la création de compte (avec le département qui filtre les N+1) et l'enregistrement de la signature en trois essais.

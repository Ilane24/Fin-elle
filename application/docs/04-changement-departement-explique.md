# Changement expliqué : le département remplace le service, le matricule disparaît

**Ce que vous avez demandé** : dans le formulaire de création de compte, retirer le champ « Service » (le département suffit) et le champ « Matricule ».

**Ce que cela change** : ces deux champs n'étaient pas que des cases du formulaire. Ils correspondaient à des colonnes et à une table de la base de données. Retirer les champs du formulaire obligeait donc à revoir la façon dont la base range les utilisateurs.

---

## 1. Avant / après

**Avant**

```
departement 1 ──── n service 1 ──── n utilisateur (avec un matricule)
```

L'utilisateur était rattaché à un **service**, et son département se **déduisait** du service : Facilities → Administration & Logistique.

**Après**

```
departement 1 ──── n utilisateur
```

L'utilisateur est rattaché **directement** à son département. La table `service` et la colonne `matricule` sont supprimées.

| Élément | Avant | Après |
|---|---|---|
| Formulaire de création de compte | Nom, prénom, **matricule**, téléphone, e-mail, fonction, département, **service**, N+1, mot de passe | Nom, prénom, e-mail, téléphone, fonction, département, N+1, mot de passe |
| Table `utilisateur` | `matricule`, `service_id` | `departement_id` |
| Table `service` | Existait | Supprimée |
| Département d'un utilisateur | Déduit du service | Enregistré directement |
| Règle « N+1 du même département » | Inchangée | Inchangée |
| Tableau de bord | Indicateur « Services » | Indicateur « Signatures enregistrées » |

---

## 2. Les fichiers modifiés

### L'entité `Utilisateur`

La propriété `$service` est remplacée par `$departement`, et `$matricule` est supprimée, avec ses méthodes :

```php
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departement $departement = null;
```

```php
    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(Departement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }
```

Avant, `getDepartement()` allait chercher le département **du service**. Maintenant, il renvoie simplement la propriété. La méthode garde le même nom : tout le code qui l'utilisait (la règle du N+1, l'affichage) continue de fonctionner sans modification. C'est l'intérêt de passer par des méthodes plutôt que d'accéder directement aux propriétés.

### L'entité `Departement`

Elle n'a plus besoin de connaître ses services. Il ne reste que l'identifiant et le nom :

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

Les fichiers `src/Entity/Service.php` et `src/Repository/ServiceRepository.php` sont supprimés.

### Le contrôleur de création de compte

Les contrôles du matricule et du service disparaissent. On récupère maintenant le département choisi, et on vérifie que le N+1 appartient à **ce** département :

```php
            if (!$departement) {
                $erreurs['departement'] = 'Choisissez votre département.';
            }
            if (!$n1 || !$departement || $n1->getDepartement() !== $departement || $n1->getRole() === 'ROLE_DEMANDEUR') {
                $erreurs['n1'] = 'Choisissez un N+1 de votre département.';
```

Puis, à la création : `->setDepartement($departement)` au lieu de `->setService($service)`.

### Le formulaire et le JavaScript

- Les blocs « Matricule » et « Service » sont retirés du gabarit `templates/inscription/index.html.twig`. L'e-mail et le téléphone sont désormais côte à côte, et le N+1 prend toute la largeur.
- Dans `public/js/inscription.js`, une seule ligne change. Le tableau des listes à filtrer ne contient plus que la liste N+1 :

```javascript
const listes = [document.querySelector('#n1')];
```

### Les données de démonstration

Les départements sont désormais une simple liste de noms, et chaque utilisateur indique directement son département (voir `src/DataFixtures/AppFixtures.php`).

---

## 3. Le point délicat : mettre à jour une base qui contient déjà des données

Sur votre Mac, la base contient déjà des utilisateurs, éventuellement avec leurs signatures. Il fallait les faire passer de « rattaché à un service » à « rattaché à un département » **sans rien perdre**.

### Ce que Doctrine avait généré automatiquement

La commande `php bin/console make:migration` a proposé ceci :

```sql
ALTER TABLE utilisateur DROP matricule, CHANGE service_id departement_id INT NOT NULL
```

C'est **faux pour des données existantes**. La commande renomme simplement la colonne `service_id` en `departement_id`, en gardant les numéros. Un utilisateur du service n° 4 se serait retrouvé dans le département n° 4, qui n'a rien à voir. Doctrine ne connaît que la **structure** des tables : il ne sait pas comment passer d'un service à son département.

> **À retenir : une migration générée se relit toujours avant d'être exécutée**, surtout quand une colonne disparaît ou change de sens.

### La migration réécrite à la main

`migrations/Version20261125162841.php` :

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125162841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Le département est rattaché directement à l'utilisateur ; suppression du matricule et de la table service";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD departement_id INT DEFAULT NULL');
        $this->addSql('UPDATE utilisateur u INNER JOIN service s ON s.id = u.service_id SET u.departement_id = s.departement_id');
        $this->addSql('ALTER TABLE utilisateur MODIFY departement_id INT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3CCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('CREATE INDEX IDX_1D1C63B3CCF9E01E ON utilisateur (departement_id)');

        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3ED5CA9E6');
        $this->addSql('DROP INDEX IDX_1D1C63B3ED5CA9E6 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP service_id');

        $this->addSql('DROP INDEX UNIQ_1D1C63B312B2DC9C ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP matricule');

        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2CCF9E01E');
        $this->addSql('DROP TABLE service');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Les services et les matricules supprimés ne peuvent pas être recréés.');
    }
}
```

Elle se lit dans l'ordre :

1. **Ajouter** la colonne `departement_id`, vide pour l'instant (`DEFAULT NULL`). Elle ne peut pas être obligatoire dès le départ, car les lignes existantes n'ont pas encore de valeur.
2. **Remplir** la colonne avec un `UPDATE ... INNER JOIN`. Pour chaque utilisateur, on va chercher son service (`s.id = u.service_id`) et on recopie le département de ce service (`u.departement_id = s.departement_id`). C'est le cœur du changement : **on recopie l'information avant de supprimer sa source**.
3. **Rendre la colonne obligatoire** (`MODIFY departement_id INT NOT NULL`), maintenant que toutes les lignes ont une valeur.
4. **Créer la clé étrangère et l'index** vers `departement`.
5. **Supprimer l'ancienne colonne** `service_id` : d'abord sa clé étrangère, puis son index, puis la colonne. MySQL refuse de supprimer une colonne encore utilisée par une clé étrangère.
6. **Supprimer le matricule** : son index unique, puis la colonne.
7. **Supprimer la table `service`** : d'abord sa clé étrangère vers `departement`, puis la table.

**`down()`** sert normalement à revenir en arrière. Ici, c'est impossible : les services et les matricules supprimés ne peuvent pas être reconstitués. On le dit clairement avec `throwIrreversibleMigrationException`. Si quelqu'un tente de revenir en arrière, il obtient ce message au lieu d'une base à moitié cassée.

### Vérification faite

Sur une base de test contenant 15 utilisateurs et leurs signatures :

- le département de chaque utilisateur est **identique** avant et après la migration ;
- les signatures enregistrées avant la migration restent lisibles ;
- `php bin/console doctrine:schema:validate` confirme que la base correspond exactement aux entités.

---

## 4. Mettre à jour votre copie

Dans le dossier `application` :

```bash
composer install
php bin/console doctrine:migrations:migrate
composer start
```

- `doctrine:migrations:migrate` exécute seulement les migrations que votre base n'a pas encore reçues, dont celle-ci. Vos utilisateurs et leurs signatures sont conservés.
- Pour repartir de données de démonstration propres, utilisez plutôt `composer base`.

---

## 5. Une conséquence à régler plus tard : Facilities

Le circuit prévoit que « toutes les personnes du service FACILITIES » traitent les achats (étape 6). Sans service, il faudra reconnaître ces personnes autrement. Deux possibilités simples :

- **un département « Facilities »** : les personnes de Facilities choisissent ce département à l'inscription ;
- **un rôle « Facilities »** : l'administrateur l'attribue aux personnes concernées.

La question sera tranchée au moment de construire cette partie.

---

## 6. À retenir pour un autre projet

1. Retirer un champ d'un formulaire peut toucher la **base de données**. Il faut regarder où la donnée est utilisée avant de la supprimer.
2. Passer par des **méthodes** (`getDepartement()`) plutôt que d'accéder directement aux propriétés permet de changer la façon dont une information est stockée sans toucher au reste du code.
3. Pour transformer une colonne qui contient des données : **ajouter** la nouvelle colonne, **recopier** les données, **rendre obligatoire**, puis **supprimer** l'ancienne.
4. **Relire** chaque migration générée avant de l'exécuter.
5. Une migration déjà exécutée ne se modifie jamais : on en **ajoute** une nouvelle.

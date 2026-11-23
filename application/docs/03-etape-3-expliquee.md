# Étape 3 expliquée : connexion, création de compte et signature

Ce document reprend **tout le code ajouté à l'étape 3**, avec ses explications. Il fait suite à `02-etapes-1-et-2-expliquees.md`.

**Ce que fait l'application à la fin de cette étape**

- Toutes les pages sont réservées aux personnes connectées. La page Utilisateurs est réservée à l'administrateur.
- On se connecte avec son e-mail professionnel et son mot de passe. Un compte désactivé est refusé.
- La création de compte demande le département. Le département filtre les services et les N+1 proposés, et le serveur revérifie que le N+1 appartient bien au même département. L'option « compte menues dépenses » reçoit son numéro automatiquement.
- Tant que la signature n'est pas enregistrée, toutes les pages renvoient vers l'enregistrement de la signature.
- La signature se fait en trois essais, pas à pas. Le serveur garde l'essai qui ressemble le plus aux deux autres, puis le stocke **chiffré** et lié au compte.

**Pour mettre à jour votre copie**

1. Récupérez la nouvelle version (ZIP ou `git pull`).
2. Tapez `composer install`, puis `php bin/console doctrine:migrations:migrate` (ou `composer base` pour repartir de données de démonstration propres).
3. Lancez `composer start`.
4. Connectez-vous, par exemple avec `fatou.diallo@cofina-demo.com` / `demo2026`. L'administratrice est `mireille.gbagbo@cofina-demo.com`.

**Sommaire**

1. Le parcours d'une connexion
2. La configuration de la sécurité
3. La page de connexion
4. Bloquer les comptes désactivés
5. La création de compte
6. Le JavaScript de la création de compte
7. La table des signatures
8. Le chiffrement de la signature
9. Le choix de l'essai le plus représentatif
10. L'enregistrement de la signature (contrôleur)
11. Le JavaScript de la signature
12. La signature obligatoire avant tout le reste
13. La mise en page : personne connectée et menus
14. Les nouvelles migrations SQL
15. À retenir pour un autre projet

---

## 1. Le parcours d'une connexion

```
1. On ouvre http://localhost:8000/        ─► pas connecté : Symfony redirige vers /connexion
2. On remplit le formulaire et on valide   ─► le formulaire est envoyé à /connexion (POST)
3. Le pare-feu de Symfony intercepte l'envoi :
      a. il cherche l'utilisateur par son e-mail       (provider « utilisateurs »)
      b. il vérifie que le compte est actif             (VerificationCompte)
      c. il compare le mot de passe avec le mot de passe chiffré
4. Si c'est bon : Symfony ouvre une session (un cookie) et redirige vers l'accueil.
   Sinon : retour à /connexion avec un message d'erreur.
5. À chaque page suivante, le cookie de session indique qui est connecté.
```

Point important : **on n'écrit pas nous-mêmes la vérification du mot de passe**. Symfony la fait, à partir de la configuration de la section suivante. Notre code se contente d'afficher le formulaire.

---

## 2. La configuration de la sécurité : `config/packages/security.yaml`

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
            user_checker: App\Security\VerificationCompte
            form_login:
                login_path: connexion
                check_path: connexion
                username_parameter: email
                password_parameter: motDePasse
                enable_csrf: true
                default_target_path: accueil
            logout:
                path: deconnexion
                target: connexion

    access_control:
        - { path: ^/(connexion|inscription), roles: PUBLIC_ACCESS }
        - { path: ^/utilisateurs, roles: ROLE_ADMIN }
        - { path: ^/, roles: ROLE_USER }
```

- **`providers`** : où trouver les utilisateurs. Ici, dans l'entité `Utilisateur`, en cherchant par `email`.
- **`firewalls.main`** : le pare-feu qui protège toute l'application.
  - `user_checker` : notre classe qui refuse les comptes désactivés (section 4).
  - `form_login` : la connexion par formulaire.
    - `login_path` : la page qui affiche le formulaire ;
    - `check_path` : l'adresse où il est envoyé (la même) ;
    - `username_parameter` et `password_parameter` : les noms des champs du formulaire ;
    - `enable_csrf` : le formulaire doit contenir un jeton anti-falsification (expliqué plus bas) ;
    - `default_target_path` : la page affichée après la connexion.
  - `logout` : l'adresse `/deconnexion` ferme la session puis renvoie vers la connexion.
- **`access_control`** : qui a le droit de voir quoi. Les règles sont lues **dans l'ordre**, et la première qui correspond s'applique.
  1. `/connexion` et `/inscription` sont ouvertes à tous (`PUBLIC_ACCESS`).
  2. `/utilisateurs` est réservée à `ROLE_ADMIN`.
  3. Tout le reste (`^/`) demande d'être connecté (`ROLE_USER`).

  Les adresses sont des **expressions régulières** : `^` signifie « commence par ».

**Le jeton CSRF.** Sans protection, un autre site pourrait envoyer un formulaire à notre application à votre place, pendant que vous êtes connecté. Pour l'éviter, chaque formulaire contient un champ caché avec un jeton secret, valable seulement pour votre session. Symfony vérifie ce jeton à la réception. On le retrouve dans les trois formulaires de cette étape.

---

## 3. La page de connexion

### `src/Controller/SecurityController.php`

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'connexion')]
    public function connexion(AuthenticationUtils $authentification): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('accueil');
        }

        $erreur = $authentification->getLastAuthenticationError();
        $message = match (true) {
            $erreur === null => null,
            $erreur instanceof CustomUserMessageAccountStatusException => $erreur->getMessageKey(),
            $erreur instanceof InvalidCsrfTokenException => 'Le formulaire a expiré. Recommencez.',
            default => 'E-mail ou mot de passe incorrect.',
        };

        return $this->render('securite/connexion.html.twig', [
            'dernierEmail' => $authentification->getLastUsername(),
            'erreur' => $message,
        ]);
    }

    #[Route('/deconnexion', name: 'deconnexion')]
    public function deconnexion(): never
    {
        throw new \LogicException('Cette méthode est interceptée par le pare-feu (logout dans security.yaml).');
    }
}
```

- `AuthenticationUtils` donne deux informations après un échec : le dernier e-mail saisi, pour ne pas le retaper, et l'erreur.
- `match (true) { ... }` choisit le message en français selon le type d'erreur. La première condition vraie l'emporte. Pour un mauvais mot de passe, on reste volontairement vague (« E-mail ou mot de passe incorrect »), pour ne pas révéler si l'e-mail existe.
- La méthode `deconnexion()` ne s'exécute jamais : le pare-feu intercepte l'adresse `/deconnexion` avant elle. Elle existe seulement pour que la route soit déclarée. Le type de retour `never` signifie « cette fonction ne renvoie jamais rien ».

### `templates/securite/connexion.html.twig`

```twig
{% extends 'base_simple.html.twig' %}

{% block title %}Connexion{% endblock %}

{% block body %}
    <h1>Connexion</h1>
    <p class="muted">Accès réservé aux collaborateurs.</p>

    {% if erreur %}
        <div class="alert alert-error">{{ erreur }}</div>
    {% endif %}

    <form method="post" action="{{ path('connexion') }}" class="formulaire">
        <div class="champ">
            <label for="email">E-mail professionnel</label>
            <input class="input" type="email" id="email" name="email" value="{{ dernierEmail }}" autocomplete="username" required autofocus>
        </div>
        <div class="champ">
            <label for="motDePasse">Mot de passe</label>
            <input class="input" type="password" id="motDePasse" name="motDePasse" autocomplete="current-password" required>
        </div>
        <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
        <button class="btn btn-primary btn-large" type="submit">Se connecter</button>
    </form>

    <p class="bas-de-carte">Pas encore de compte ? <a href="{{ path('inscription') }}">Créer un compte</a></p>
{% endblock %}
```

- Les champs s'appellent `email` et `motDePasse`, les noms déclarés dans `security.yaml`.
- `csrf_token('authenticate')` produit le jeton anti-falsification que Symfony attend pour la connexion.
- Cette page utilise une mise en page simple, une carte centrée sans menu : `templates/base_simple.html.twig`.

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
<body class="page-simple">
<div class="carte-simple {% block largeur %}{% endblock %}">
    <img class="logo-grand" src="{{ asset('images/logo-finelle.png') }}" alt="Fin'Elle – Filiale du Groupe Cofina">

    {% for type, messages in app.flashes %}
        {% for message in messages %}
            <div class="alert alert-{{ type }}" data-auto-fermeture>{{ message }}</div>
        {% endfor %}
    {% endfor %}

    {% block body %}{% endblock %}
</div>
</body>
</html>
```

`{% block largeur %}` permet à une page d'élargir la carte. La création de compte et la signature y ajoutent la classe `carte-large`.

---

## 4. Bloquer les comptes désactivés : `src/Security/VerificationCompte.php`

```php
<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VerificationCompte implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $utilisateur): void
    {
        if ($utilisateur instanceof Utilisateur && !$utilisateur->isActif()) {
            throw new CustomUserMessageAccountStatusException("Ce compte est désactivé. Contactez l'administrateur.");
        }
    }

    public function checkPostAuth(UserInterface $utilisateur): void
    {
    }
}
```

- Un **user checker** est appelé par Symfony à chaque connexion. `checkPreAuth` s'exécute **avant** la vérification du mot de passe.
- Si le compte est désactivé, on lève une exception avec un message. Symfony arrête la connexion et transmet ce message à la page de connexion (le cas `CustomUserMessageAccountStatusException` du `match` de la section 3).
- Ainsi, un ancien collaborateur dont l'administrateur a désactivé le compte ne peut plus se connecter. Son historique reste en base.

---

## 5. La création de compte

### `src/Controller/InscriptionController.php`

```php
<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\DepartementRepository;
use App\Repository\ServiceRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'inscription')]
    public function inscription(
        Request $request,
        DepartementRepository $departements,
        ServiceRepository $services,
        UtilisateurRepository $utilisateurs,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        Security $security,
        #[Autowire('%env(APP_DOMAINE_EMAIL)%')] string $domaine,
    ): Response {
        $saisie = $request->request->all();
        $erreurs = [];

        if ($request->isMethod('POST')) {
            $champ = fn (string $nom): string => trim((string) ($saisie[$nom] ?? ''));
            $service = $services->find((int) $champ('service'));
            $n1 = $utilisateurs->find((int) $champ('n1'));
            $email = mb_strtolower($champ('email'));

            if (!$this->isCsrfTokenValid('inscription', $champ('_token'))) {
                $erreurs['formulaire'] = 'Le formulaire a expiré. Rechargez la page et recommencez.';
            }
            if ($champ('nom') === '') {
                $erreurs['nom'] = 'Saisissez votre nom.';
            }
            if ($champ('prenom') === '') {
                $erreurs['prenom'] = 'Saisissez votre prénom.';
            }
            if ($champ('matricule') === '') {
                $erreurs['matricule'] = 'Saisissez votre matricule.';
            } elseif ($utilisateurs->findOneBy(['matricule' => $champ('matricule')])) {
                $erreurs['matricule'] = 'Ce matricule est déjà utilisé.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreurs['email'] = 'Saisissez une adresse e-mail valide.';
            } elseif (!str_ends_with($email, '@'.$domaine)) {
                $erreurs['email'] = 'Utilisez votre adresse @'.$domaine.'.';
            } elseif ($utilisateurs->findOneBy(['email' => $email])) {
                $erreurs['email'] = 'Un compte existe déjà avec cette adresse.';
            }
            if ($champ('fonction') === '') {
                $erreurs['fonction'] = 'Saisissez votre fonction.';
            }
            if (!$service || $service->getDepartement()?->getId() !== (int) $champ('departement')) {
                $erreurs['service'] = 'Choisissez un service de votre département.';
            }
            if (!$n1 || !$service || $n1->getDepartement() !== $service->getDepartement() || $n1->getRole() === 'ROLE_DEMANDEUR') {
                $erreurs['n1'] = 'Choisissez un N+1 de votre département.';
            }
            if (!preg_match('/^(?=.*\d).{8,}$/', $champ('motDePasse'))) {
                $erreurs['motDePasse'] = '8 caractères minimum, dont au moins 1 chiffre.';
            } elseif ($champ('motDePasse') !== $champ('confirmation')) {
                $erreurs['confirmation'] = 'Les deux mots de passe ne correspondent pas.';
            }

            if (!$erreurs) {
                $utilisateur = new Utilisateur();
                $utilisateur
                    ->setNom($champ('nom'))
                    ->setPrenom($champ('prenom'))
                    ->setMatricule($champ('matricule'))
                    ->setTelephone($champ('telephone') ?: null)
                    ->setEmail($email)
                    ->setFonction($champ('fonction'))
                    ->setService($service)
                    ->setN1($n1)
                    ->setMotDePasse($hasher->hashPassword($utilisateur, $champ('motDePasse')));
                if (isset($saisie['compteMenuesDepenses'])) {
                    $utilisateur->ouvrirCompteMenuesDepenses($utilisateurs->prochainNumeroCompteMd());
                }

                $em->persist($utilisateur);
                $em->flush();

                $security->login($utilisateur, 'form_login', 'main');
                $this->addFlash('success', 'Compte créé. Enregistrez maintenant votre signature.');

                return $this->redirectToRoute('signature');
            }
        }

        return $this->render('inscription/index.html.twig', [
            'departements' => $departements->findBy([], ['nom' => 'ASC']),
            'responsables' => $utilisateurs->findResponsables(),
            'saisie' => $saisie,
            'erreurs' => $erreurs,
            'domaine' => $domaine,
        ]);
    }
}
```

Ce contrôleur fait trois choses, dans l'ordre :

1. **Afficher le formulaire** (arrivée sur la page, méthode GET). On envoie au gabarit les départements avec leurs services, et la liste des responsables.
2. **Contrôler la saisie** (envoi du formulaire, méthode POST). Chaque erreur est rangée dans le tableau `$erreurs`, sous le nom du champ concerné. Le gabarit l'affiche sous ce champ.
3. **Créer le compte** s'il n'y a aucune erreur. Le compte est enregistré, la personne est connectée tout de suite, puis envoyée vers l'enregistrement de sa signature.

Les points importants :

- `$request->request->all()` récupère tout ce que le formulaire a envoyé, sous forme de tableau. On le renvoie au gabarit (`saisie`) pour **pré-remplir les champs** en cas d'erreur : la personne n'a pas à tout retaper.
- `$champ = fn (string $nom): string => trim(...)` est une **fonction fléchée** : une petite fonction rangée dans une variable. `$champ('nom')` renvoie la valeur du champ `nom`, nettoyée des espaces avant et après.
- `#[Autowire('%env(APP_DOMAINE_EMAIL)%')] string $domaine` lit la variable `APP_DOMAINE_EMAIL` du fichier `.env` (`cofina-demo.com`). Le domaine autorisé n'est donc pas écrit en dur dans le code : on le changera dans `.env` pour le vrai domaine de l'entreprise.
- **Le contrôle du N+1 est refait côté serveur** : il doit exister, appartenir au même département que le service choisi, et être un responsable (pas un simple demandeur). Le filtrage des listes en JavaScript ne sert qu'au confort : n'importe qui peut modifier une page dans son navigateur. **La règle de sécurité est toujours vérifiée par le serveur.**
- `preg_match('/^(?=.*\d).{8,}$/', ...)` vérifie le mot de passe : au moins 8 caractères (`.{8,}`), dont au moins un chiffre (`(?=.*\d)`).
- `$hasher->hashPassword(...)` chiffre le mot de passe avant de l'enregistrer.
- `$utilisateurs->prochainNumeroCompteMd()` calcule le numéro du compte menues dépenses (voir ci-dessous).
- `$security->login($utilisateur, 'form_login', 'main')` connecte la personne juste après la création de son compte, sans lui redemander son mot de passe.
- `$this->addFlash('success', '...')` prépare le message affiché sur la page suivante.

### Les nouvelles recherches : `src/Repository/UtilisateurRepository.php`

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

- **`findResponsables()`** utilise le **QueryBuilder**, qui construit une requête morceau par morceau. Elle équivaut à :

  ```sql
  SELECT * FROM utilisateur u
  WHERE u.actif = 1 AND u.role != 'ROLE_DEMANDEUR'
  ORDER BY u.nom ASC
  ```

  `setParameter('demandeur', ...)` remplace `:demandeur` par la valeur, de façon sûre. C'est ce qui protège contre les **injections SQL** : la valeur n'est jamais collée directement dans le texte de la requête.
- **`prochainNumeroCompteMd()`** compte les comptes déjà ouverts, ajoute 1 et forme le numéro : `CMD-2026-004`. `%03d` signifie « nombre sur 3 chiffres, complété par des zéros ».

### `templates/inscription/index.html.twig`

```twig
{% extends 'base_simple.html.twig' %}

{% block title %}Créer un compte{% endblock %}
{% block largeur %}carte-large{% endblock %}

{% block javascripts %}
    <script src="{{ asset('js/inscription.js') }}" defer></script>
{% endblock %}

{% macro erreur(erreurs, nom) %}
    {% if erreurs[nom] is defined %}<span class="erreur">{{ erreurs[nom] }}</span>{% endif %}
{% endmacro %}

{% block body %}
    {% import _self as f %}
    <h1>Créer un compte</h1>
    <p class="muted">Les champs marqués * sont obligatoires.</p>

    {% if erreurs.formulaire is defined %}
        <div class="alert alert-error">{{ erreurs.formulaire }}</div>
    {% endif %}

    <form method="post" class="formulaire" novalidate>
        <h2 class="partie">Identification</h2>
        <div class="grille-2">
            <div class="champ">
                <label for="nom">Nom *</label>
                <input class="input" id="nom" name="nom" value="{{ saisie.nom ?? '' }}">
                {{ f.erreur(erreurs, 'nom') }}
            </div>
            <div class="champ">
                <label for="prenom">Prénom *</label>
                <input class="input" id="prenom" name="prenom" value="{{ saisie.prenom ?? '' }}">
                {{ f.erreur(erreurs, 'prenom') }}
            </div>
            <div class="champ">
                <label for="matricule">Matricule *</label>
                <input class="input" id="matricule" name="matricule" value="{{ saisie.matricule ?? '' }}" placeholder="Ex. CF-01234">
                {{ f.erreur(erreurs, 'matricule') }}
            </div>
            <div class="champ">
                <label for="telephone">Téléphone</label>
                <input class="input" id="telephone" name="telephone" value="{{ saisie.telephone ?? '' }}" placeholder="+225 00 00 00 00 00">
            </div>
            <div class="champ pleine-largeur">
                <label for="email">E-mail professionnel *</label>
                <input class="input" type="email" id="email" name="email" value="{{ saisie.email ?? '' }}" placeholder="prenom.nom@{{ domaine }}">
                {{ f.erreur(erreurs, 'email') }}
            </div>
        </div>

        <h2 class="partie">Compte</h2>
        <div class="grille-2">
            <div class="champ">
                <label for="fonction">Fonction *</label>
                <input class="input" id="fonction" name="fonction" value="{{ saisie.fonction ?? '' }}" placeholder="Ex. Assistante administrative">
                {{ f.erreur(erreurs, 'fonction') }}
            </div>
            <div class="champ">
                <label for="departement">Département *</label>
                <select class="input" id="departement" name="departement" data-filtre-departement>
                    <option value="">Choisir…</option>
                    {% for d in departements %}
                        <option value="{{ d.id }}" {{ (saisie.departement ?? '') == d.id ? 'selected' }}>{{ d.nom }}</option>
                    {% endfor %}
                </select>
            </div>
            <div class="champ">
                <label for="service">Service *</label>
                <select class="input" id="service" name="service">
                    <option value="">Choisissez d'abord le département</option>
                    {% for d in departements %}
                        {% for s in d.services %}
                            <option value="{{ s.id }}" data-departement="{{ d.id }}" {{ (saisie.service ?? '') == s.id ? 'selected' }}>{{ s.libelle }}</option>
                        {% endfor %}
                    {% endfor %}
                </select>
                {{ f.erreur(erreurs, 'service') }}
            </div>
            <div class="champ">
                <label for="n1">Supérieur hiérarchique (N+1) *</label>
                <select class="input" id="n1" name="n1">
                    <option value="">Choisissez d'abord le département</option>
                    {% for r in responsables %}
                        <option value="{{ r.id }}" data-departement="{{ r.departement.id }}" {{ (saisie.n1 ?? '') == r.id ? 'selected' }}>{{ r.nomComplet }} – {{ r.fonction }}</option>
                    {% endfor %}
                </select>
                {{ f.erreur(erreurs, 'n1') }}
                <span class="aide">Seuls les responsables de votre département peuvent signer vos FED.</span>
            </div>
            <div class="champ">
                <label for="motDePasse">Mot de passe *</label>
                <input class="input" type="password" id="motDePasse" name="motDePasse" placeholder="8 caractères dont 1 chiffre" autocomplete="new-password">
                {{ f.erreur(erreurs, 'motDePasse') }}
            </div>
            <div class="champ">
                <label for="confirmation">Confirmation *</label>
                <input class="input" type="password" id="confirmation" name="confirmation" autocomplete="new-password">
                {{ f.erreur(erreurs, 'confirmation') }}
            </div>
        </div>

        <h2 class="partie">Option</h2>
        <label class="option">
            <input type="checkbox" name="compteMenuesDepenses" {{ saisie.compteMenuesDepenses is defined ? 'checked' }}>
            <span><b>Compte menues dépenses</b><br><span class="muted">Obligatoire pour effectuer une menue dépense.</span></span>
        </label>

        <input type="hidden" name="_token" value="{{ csrf_token('inscription') }}">
        <button class="btn btn-primary btn-large" type="submit">Créer mon compte</button>
    </form>

    <p class="bas-de-carte">Déjà inscrit(e) ? <a href="{{ path('connexion') }}">Se connecter</a></p>
{% endblock %}
```

- **La macro `erreur`** est une petite fonction Twig réutilisable. `{{ f.erreur(erreurs, 'nom') }}` affiche le message d'erreur du champ `nom` s'il y en a un. On évite ainsi de répéter le même `{% if %}` sous chaque champ.
- `value="{{ saisie.nom ?? '' }}"` remet la valeur saisie après une erreur. `??` signifie « si elle n'existe pas, alors chaîne vide ». Les mots de passe ne sont volontairement **jamais** remis.
- **Les listes Service et N+1 contiennent toutes les options**, chacune marquée d'un attribut `data-departement`. C'est le JavaScript (section 6) qui n'affiche que celles du département choisi.
- `novalidate` sur le formulaire désactive les contrôles automatiques du navigateur, pour que tous les messages viennent du serveur, en français et au même endroit.

---

## 6. Le JavaScript de la création de compte : `public/js/inscription.js`

```javascript
document.addEventListener('DOMContentLoaded', () => {
    const departement = document.querySelector('[data-filtre-departement]');
    const listes = [document.querySelector('#service'), document.querySelector('#n1')];
    const optionsParListe = new Map(listes.map((liste) => [liste, [...liste.querySelectorAll('option[data-departement]')]]));

    const filtrer = () => {
        listes.forEach((liste) => {
            const choixActuel = liste.value;
            const options = optionsParListe.get(liste);
            options.forEach((option) => option.remove());

            const duDepartement = options.filter((option) => option.dataset.departement === departement.value);
            duDepartement.forEach((option) => liste.append(option));

            liste.value = duDepartement.some((option) => option.value === choixActuel) ? choixActuel : '';
            liste.disabled = departement.value === '';
            liste.options[0].textContent = departement.value === '' ? "Choisissez d'abord le département" : 'Choisir…';
        });
    };

    departement.addEventListener('change', filtrer);
    filtrer();
});
```

**Ce qu'il fait** : quand on choisit un département, les listes « Service » et « N+1 » ne gardent que les choix de ce département.

**Comment** :

1. Au chargement, il mémorise toutes les options des deux listes qui portent un `data-departement` (dans une `Map`, une sorte de tableau associatif : liste → ses options).
2. À chaque changement de département (`change`), la fonction `filtrer` :
   - retire toutes les options de la liste ;
   - remet seulement celles dont `data-departement` est égal au département choisi ;
   - garde la sélection si elle est toujours valable, sinon la vide ;
   - désactive la liste tant qu'aucun département n'est choisi, et change le premier texte (« Choisissez d'abord le département » / « Choisir… »).
3. `filtrer()` est aussi appelée une première fois au chargement. Après une erreur, la page revient avec le département déjà choisi, et les listes sont tout de suite filtrées.

**Pourquoi retirer les options plutôt que les cacher ?** Safari ne sait pas masquer une option de liste déroulante. En les retirant puis en les remettant, le script fonctionne dans tous les navigateurs.

**Sans ce script**, les listes montreraient tous les services et tous les responsables. Le serveur refuserait quand même un mauvais choix (section 5).

---

## 7. La table des signatures : `src/Entity/SignatureReference.php`

```php
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
```

- **`#[ORM\OneToOne(inversedBy: 'signature')]`** : une relation **un pour un**. Un utilisateur a au plus une signature de référence, et une signature appartient à un seul utilisateur. Doctrine crée une colonne `utilisateur_id` **unique**.
- **`imageChiffree`** et **`traitsChiffres`** sont de type `TEXT` (`LONGTEXT` en MySQL), car ils peuvent être longs. Ils ne contiennent que du texte chiffré, illisible sans la clé :
  - l'image est le PNG transparent de la signature ;
  - les traits sont les coordonnées des points tracés.
- **`essaiRetenu`** indique l'essai gardé (1, 2 ou 3).
- **Le constructeur** reçoit tout ce qui est obligatoire. Il est donc impossible de créer une signature incomplète. Il appelle aussi `$utilisateur->setSignature($this)`, pour que les deux côtés de la relation soient à jour tout de suite.

Côté `Utilisateur`, on a ajouté l'autre côté de la relation :

```php
    #[ORM\OneToOne(mappedBy: 'utilisateur')]
    private ?SignatureReference $signature = null;
```

`mappedBy` signifie que la colonne de liaison est dans l'autre table : `signature_reference.utilisateur_id`. Cela permet d'écrire `$utilisateur->getSignature()`, ou `app.user.signature` dans Twig.

---

## 8. Le chiffrement de la signature : `src/Service/ChiffrementSignature.php`

```php
<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ChiffrementSignature
{
    private string $cle;

    public function __construct(#[Autowire('%env(APP_SIGNATURE_KEY)%')] string $cleBase64)
    {
        $this->cle = base64_decode($cleBase64);
    }

    public function chiffrer(string $texte, Utilisateur $proprietaire): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $chiffre = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($texte, $this->lien($proprietaire), $nonce, $this->cle);

        return base64_encode($nonce.$chiffre);
    }

    public function dechiffrer(string $donnees, Utilisateur $proprietaire): ?string
    {
        $brut = base64_decode($donnees);
        $nonce = substr($brut, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $chiffre = substr($brut, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $texte = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($chiffre, $this->lien($proprietaire), $nonce, $this->cle);

        return $texte === false ? null : $texte;
    }

    private function lien(Utilisateur $proprietaire): string
    {
        return 'utilisateur-'.$proprietaire->getId();
    }
}
```

Un **service** est une classe qui rend un service précis au reste de l'application. Symfony la fournit automatiquement à qui la demande en paramètre, comme les repositories.

- **La clé** vient du fichier `.env` (`APP_SIGNATURE_KEY`). C'est une suite de 32 octets aléatoires, écrite en base64. Sans cette clé, les signatures stockées en base sont illisibles.

  > Sur le serveur de l'entreprise, il faudra générer **une autre clé**, la mettre dans `.env.local` et ne jamais la partager. Commande pour en générer une : `php -r "echo base64_encode(random_bytes(32));"`. Attention : si la clé est perdue ou changée, les signatures déjà enregistrées ne peuvent plus être lues.

- **`chiffrer()`** :
  1. crée un `nonce`, un nombre aléatoire différent à chaque chiffrement. Deux chiffrements du même texte donnent ainsi deux résultats différents ;
  2. chiffre avec `sodium_crypto_aead_xchacha20poly1305_ietf_encrypt`, un algorithme moderne et sûr, intégré à PHP (extension `sodium`) ;
  3. colle le nonce devant le texte chiffré, puis transforme le tout en texte (`base64_encode`) pour pouvoir le ranger dans une colonne `TEXT`.
- **`dechiffrer()`** fait l'inverse : il sépare le nonce du texte chiffré, puis déchiffre. Il renvoie `null` si c'est impossible : mauvaise clé, données modifiées, ou mauvais propriétaire.
- **Le lien avec l'utilisateur** : le texte `utilisateur-5` (méthode `lien()`) est passé comme « donnée associée ». Il n'est pas chiffré, mais il est **vérifié** au déchiffrement. Si quelqu'un copie en base la signature chiffrée de l'utilisateur 5 sur l'utilisateur 8, le déchiffrement échoue. Une signature ne peut donc servir qu'à son propriétaire.

---

## 9. Le choix de l'essai le plus représentatif : `src/Service/ComparaisonSignatures.php`

```php
<?php

namespace App\Service;

class ComparaisonSignatures
{
    private const TAILLE_GRILLE = 24;

    /**
     * @param array<int, array<int, array<int, array{0: float, 1: float}>>> $essais
     */
    public function choisirEssai(array $essais): int
    {
        $grilles = array_map(fn (array $traits) => $this->grille($traits), $essais);

        $meilleurEssai = 0;
        $meilleurScore = -1.0;
        foreach ($grilles as $i => $grille) {
            $score = 0.0;
            foreach ($grilles as $j => $autre) {
                if ($i !== $j) {
                    $score += $this->ressemblance($grille, $autre);
                }
            }
            if ($score > $meilleurScore) {
                $meilleurScore = $score;
                $meilleurEssai = $i;
            }
        }

        return $meilleurEssai;
    }

    private function grille(array $traits): array
    {
        $points = array_merge(...$traits);
        $xs = array_column($points, 0);
        $ys = array_column($points, 1);
        $minX = min($xs);
        $minY = min($ys);
        $taille = max(max($xs) - $minX, max($ys) - $minY, 1);

        $cases = [];
        foreach ($traits as $trait) {
            foreach ($trait as $k => [$x, $y]) {
                [$xPrecedent, $yPrecedent] = $k > 0 ? $trait[$k - 1] : [$x, $y];
                $pas = max(1, (int) ceil(hypot($x - $xPrecedent, $y - $yPrecedent) / $taille * self::TAILLE_GRILLE * 2));
                for ($p = 0; $p <= $pas; $p++) {
                    $px = $xPrecedent + ($x - $xPrecedent) * $p / $pas;
                    $py = $yPrecedent + ($y - $yPrecedent) * $p / $pas;
                    $colonne = min(self::TAILLE_GRILLE - 1, (int) (($px - $minX) / $taille * self::TAILLE_GRILLE));
                    $ligne = min(self::TAILLE_GRILLE - 1, (int) (($py - $minY) / $taille * self::TAILLE_GRILLE));
                    $cases[$ligne.'-'.$colonne] = true;
                }
            }
        }

        return $cases;
    }

    private function ressemblance(array $a, array $b): float
    {
        $communes = count(array_intersect_key($a, $b));

        return 2 * $communes / (count($a) + count($b));
    }
}
```

**L'idée, simplement** : on pose chaque essai sur un quadrillage de 24 × 24 cases, et on note les cases traversées par le tracé. Deux signatures qui se ressemblent noircissent à peu près les mêmes cases. On garde l'essai qui a le plus de cases en commun avec les deux autres.

- **`grille()`** transforme un essai en ensemble de cases noircies :
  1. il calcule le rectangle qui entoure la signature (`min` et `max` des x et des y) ;
  2. il ramène ce rectangle à la taille du quadrillage. Une signature faite en petit ou en grand, à gauche ou à droite du cadre, donne donc le même résultat : c'est la **normalisation** de la taille et de la position ;
  3. entre deux points consécutifs d'un trait, il ajoute des points intermédiaires (`$pas`), pour ne sauter aucune case quand la souris va vite ;
  4. chaque case touchée est notée dans `$cases`, sous la forme `'ligne-colonne' => true`.
- **`ressemblance()`** compare deux grilles avec le **coefficient de Dice** : `2 × cases communes ÷ (cases de A + cases de B)`. Il vaut 1 pour deux tracés identiques et 0 pour deux tracés qui ne se croisent jamais.
- **`choisirEssai()`** additionne, pour chaque essai, sa ressemblance avec les deux autres, et renvoie le numéro de l'essai qui a le meilleur total (0, 1 ou 2).

Conformément à votre demande, il n'y a **ni seuil ni refus** : l'utilisateur fait trois essais, et on garde le meilleur.

---

## 10. L'enregistrement de la signature : `src/Controller/SignatureController.php`

```php
<?php

namespace App\Controller;

use App\Entity\SignatureReference;
use App\Entity\Utilisateur;
use App\Service\ChiffrementSignature;
use App\Service\ComparaisonSignatures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SignatureController extends AbstractController
{
    private const TAILLE_MAX_IMAGE = 500000;

    #[Route('/ma-signature', name: 'signature', methods: ['GET'])]
    public function afficher(ChiffrementSignature $chiffrement): Response
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        $signature = $utilisateur->getSignature();

        if ($signature === null) {
            return $this->render('signature/enregistrer.html.twig');
        }

        return $this->render('signature/afficher.html.twig', [
            'image' => $chiffrement->dechiffrer($signature->getImageChiffree(), $utilisateur),
            'signature' => $signature,
        ]);
    }

    #[Route('/ma-signature', name: 'signature_enregistrer', methods: ['POST'])]
    public function enregistrer(
        Request $request,
        ComparaisonSignatures $comparaison,
        ChiffrementSignature $chiffrement,
        EntityManagerInterface $em,
    ): Response {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        if ($utilisateur->getSignature() !== null) {
            return $this->redirectToRoute('signature');
        }
        if (!$this->isCsrfTokenValid('signature', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Le formulaire a expiré. Recommencez.');

            return $this->redirectToRoute('signature');
        }

        $essais = json_decode((string) $request->request->get('essais'), true);
        if (!$this->essaisValides($essais)) {
            $this->addFlash('error', 'Les trois essais sont obligatoires. Recommencez.');

            return $this->redirectToRoute('signature');
        }

        $retenu = $comparaison->choisirEssai(array_column($essais, 'traits'));
        $essai = $essais[$retenu];

        $signature = new SignatureReference(
            $utilisateur,
            $chiffrement->chiffrer($essai['image'], $utilisateur),
            $chiffrement->chiffrer(json_encode($essai['traits']), $utilisateur),
            $retenu + 1,
        );
        $em->persist($signature);
        $em->flush();

        $this->addFlash('success', sprintf('Signature enregistrée : nous avons gardé votre essai %d, le plus représentatif.', $retenu + 1));

        return $this->redirectToRoute('accueil');
    }

    private function essaisValides(mixed $essais): bool
    {
        if (!is_array($essais) || count($essais) !== 3) {
            return false;
        }
        foreach ($essais as $essai) {
            $image = $essai['image'] ?? '';
            $traits = $essai['traits'] ?? [];
            if (!is_string($image) || !str_starts_with($image, 'data:image/png;base64,') || strlen($image) > self::TAILLE_MAX_IMAGE) {
                return false;
            }
            if (!is_array($traits) || $traits === []) {
                return false;
            }
            foreach ($traits as $trait) {
                if (!is_array($trait) || $trait === []) {
                    return false;
                }
                foreach ($trait as $point) {
                    if (!is_array($point) || count($point) !== 2 || !is_numeric($point[0]) || !is_numeric($point[1])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
```

- **Une même adresse, deux méthodes** : `/ma-signature` en `GET` affiche la page, et `/ma-signature` en `POST` reçoit les trois essais. `methods: ['GET']` / `['POST']` les distingue.
- **`afficher()`** : s'il n'y a pas encore de signature, la page d'enregistrement s'affiche. Sinon, on déchiffre l'image et on l'affiche (`signature/afficher.html.twig`).
- **`enregistrer()`** :
  1. refuse si la personne a déjà une signature, ou si le jeton CSRF est invalide ;
  2. décode les essais envoyés en JSON par le JavaScript (`json_decode(..., true)` donne un tableau PHP) ;
  3. **vérifie tout** avec `essaisValides()` : exactement 3 essais, une image PNG de taille raisonnable (500 Ko au plus), des traits composés de points `[x, y]` numériques. Les données venant du navigateur ne sont jamais crues sur parole ;
  4. choisit l'essai le plus représentatif (section 9) ;
  5. chiffre l'image et les traits (section 8), crée la `SignatureReference` et l'enregistre.
- `array_column($essais, 'traits')` extrait, de chaque essai, uniquement ses traits.
- `/** @var Utilisateur $utilisateur */` est un commentaire spécial qui indique à l'éditeur de code le type de la variable, pour l'autocomplétion. Il ne change rien à l'exécution.

### `templates/signature/enregistrer.html.twig`

```twig
{% extends 'base_simple.html.twig' %}

{% block title %}Enregistrez votre signature{% endblock %}
{% block largeur %}carte-large{% endblock %}

{% block javascripts %}
    <script src="{{ asset('js/signature.js') }}" defer></script>
{% endblock %}

{% block body %}
    <h1>Enregistrez votre signature</h1>
    <p class="muted">Étape obligatoire, {{ app.user.prenom }}. Votre signature sera ensuite apposée automatiquement à chaque validation.</p>

    <ol class="etapes" id="etapes">
        <li class="en-cours">Essai 1</li>
        <li>Essai 2</li>
        <li>Essai 3</li>
    </ol>

    <div class="consignes">
        <div class="carte-consigne"><b>Signez comme sur papier</b><span>À la souris ou au stylet.</span></div>
        <div class="carte-consigne"><b>Signez en grand</b><span>Utilisez bien l'espace de signature.</span></div>
        <div class="carte-consigne"><b>Signez trois fois</b><span>Nous gardons automatiquement la plus représentative.</span></div>
    </div>

    <div class="zone-essai">
        <div>
            <h2 id="titreEssai">Essai 1 sur 3</h2>
            <canvas class="pad" id="pad"></canvas>
            <div class="sous-pad">
                <span class="erreur" id="erreurEssai"></span>
                <button type="button" class="btn btn-ghost" id="effacer">Effacer</button>
            </div>
        </div>
        <div class="vignettes" id="vignettes">
            <h3 class="muted">Vos essais</h3>
        </div>
    </div>

    <form method="post" action="{{ path('signature_enregistrer') }}" id="formulaireSignature">
        <input type="hidden" name="essais" id="essais">
        <input type="hidden" name="_token" value="{{ csrf_token('signature') }}">
        <div class="actions">
            <a href="{{ path('deconnexion') }}">Terminer plus tard (se déconnecter)</a>
            <button type="button" class="btn btn-primary" id="valider" disabled>Valider l'essai 1</button>
        </div>
    </form>
{% endblock %}
```

La page contient le cadre de dessin (`<canvas id="pad">`), la zone des vignettes et un formulaire avec deux champs cachés : les essais (`essais`, rempli par le JavaScript) et le jeton CSRF.

### `templates/signature/afficher.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Ma signature{% endblock %}

{% block body %}
    <div class="page-head">
        <div>
            <h1>Ma signature</h1>
            <p class="muted">Elle est apposée automatiquement lorsque vous validez une fiche.</p>
        </div>
    </div>

    <div class="card signature-carte">
        <div class="signature-image">
            {% if image %}
                <img src="{{ image }}" alt="Ma signature">
            {% else %}
                <span class="erreur">Impossible de lire la signature.</span>
            {% endif %}
        </div>
        <p class="muted">Enregistrée le {{ signature.creeLe|date('d/m/Y à H:i') }} · essai {{ signature.essaiRetenu }} sur 3 · stockée chiffrée.</p>
    </div>
{% endblock %}
```

`|date('d/m/Y à H:i')` affiche la date au format JJ/MM/AAAA, comme demandé dans le cahier des charges.

---

## 11. Le JavaScript de la signature : `public/js/signature.js`

```javascript
document.addEventListener('DOMContentLoaded', () => {
    const pad = document.querySelector('#pad');
    const crayon = pad.getContext('2d');
    const boutonValider = document.querySelector('#valider');
    const formulaire = document.querySelector('#formulaireSignature');
    const essais = [null, null, null];
    let essaiEnCours = 0;
    let traits = [];
    let enTrainDeDessiner = false;

    const ratio = window.devicePixelRatio || 1;
    pad.width = pad.clientWidth * ratio;
    pad.height = pad.clientHeight * ratio;
    crayon.scale(ratio, ratio);
    crayon.lineWidth = 2.2;
    crayon.lineCap = 'round';
    crayon.lineJoin = 'round';
    crayon.strokeStyle = '#2F3437';

    const position = (evenement) => {
        const cadre = pad.getBoundingClientRect();
        return [Math.round(evenement.clientX - cadre.left), Math.round(evenement.clientY - cadre.top)];
    };

    const redessiner = () => {
        crayon.clearRect(0, 0, pad.width, pad.height);
        traits.forEach((trait) => {
            crayon.beginPath();
            trait.forEach(([x, y], i) => (i === 0 ? crayon.moveTo(x, y) : crayon.lineTo(x, y)));
            crayon.stroke();
        });
        boutonValider.disabled = traits.length === 0;
    };

    pad.addEventListener('pointerdown', (evenement) => {
        enTrainDeDessiner = true;
        traits.push([position(evenement)]);
        pad.setPointerCapture(evenement.pointerId);
    });
    pad.addEventListener('pointermove', (evenement) => {
        if (!enTrainDeDessiner) {
            return;
        }
        traits.at(-1).push(position(evenement));
        redessiner();
    });
    pad.addEventListener('pointerup', () => {
        enTrainDeDessiner = false;
        redessiner();
    });

    document.querySelector('#effacer').addEventListener('click', () => {
        traits = [];
        redessiner();
    });

    const afficherEssai = (numero) => {
        essaiEnCours = numero;
        traits = essais[numero] ? essais[numero].traits.map((trait) => trait.slice()) : [];
        redessiner();

        document.querySelector('#titreEssai').textContent = `Essai ${numero + 1} sur 3`;
        document.querySelectorAll('#etapes li').forEach((etape, i) => {
            etape.className = i === numero ? 'en-cours' : essais[i] ? 'fait' : '';
        });

        const dernierManquant = essais.filter((essai, i) => essai === null && i !== numero).length === 0;
        boutonValider.textContent = dernierManquant ? 'Enregistrer ma signature' : `Valider l'essai ${numero + 1}`;

        const vignettes = document.querySelector('#vignettes');
        vignettes.querySelectorAll('.vignette').forEach((vignette) => vignette.remove());
        essais.forEach((essai, i) => {
            const vignette = document.createElement('button');
            vignette.type = 'button';
            vignette.className = 'vignette' + (i === numero ? ' en-cours' : '');
            vignette.innerHTML = `<b>Essai ${i + 1}</b>` + (essai && i !== numero ? `<img src="${essai.image}" alt="">` : '');
            vignette.disabled = !essai || i === numero;
            vignette.addEventListener('click', () => afficherEssai(i));
            vignettes.append(vignette);
        });
    };

    boutonValider.addEventListener('click', () => {
        essais[essaiEnCours] = { traits: traits.map((trait) => trait.slice()), image: pad.toDataURL('image/png') };

        const prochain = essais.findIndex((essai) => essai === null);
        if (prochain !== -1) {
            afficherEssai(prochain);
            return;
        }

        document.querySelector('#essais').value = JSON.stringify(essais);
        boutonValider.disabled = true;
        boutonValider.textContent = 'Enregistrement…';
        formulaire.submit();
    });

    afficherEssai(0);
});
```

**Ce qu'il fait** : il permet de dessiner dans le cadre, de passer d'un essai à l'autre, de revenir sur un essai déjà fait, puis d'envoyer les trois essais au serveur.

**Comment**, bloc par bloc :

1. **Préparer le cadre de dessin.** Le `canvas` est agrandi selon `devicePixelRatio`, pour que le trait reste net sur les écrans haute définition (Retina). On règle ensuite l'épaisseur, les bouts arrondis et la couleur du trait.
2. **Enregistrer le tracé.** On écoute les événements `pointer`, qui fonctionnent pour la souris, le stylet et le doigt :
   - `pointerdown` (on appuie) commence un nouveau trait ;
   - `pointermove` (on bouge) ajoute un point `[x, y]` au trait en cours et redessine ;
   - `pointerup` (on relâche) termine le trait.

   Tous les traits sont gardés dans le tableau `traits`. Ce sont ces coordonnées qui seront envoyées au serveur pour la comparaison.
3. **`redessiner()`** efface le cadre et redessine tous les traits. Le bouton « Valider » ne s'active que si quelque chose a été tracé.
4. **« Effacer »** vide le tableau `traits` et redessine un cadre vide.
5. **`afficherEssai(numero)`** passe à un essai : il remet son tracé s'il a déjà été fait, puis met à jour le titre, les étapes (en cours en rouge, faites en vert), le texte du bouton et les vignettes. Une vignette d'essai déjà fait est cliquable pour le refaire.
6. **Le bouton « Valider l'essai »** range l'essai en cours (ses traits, et son image obtenue avec `pad.toDataURL('image/png')`), puis passe au premier essai manquant. Quand les trois sont faits, il convertit les essais en texte JSON, le met dans le champ caché `essais` et envoie le formulaire.

**Sans JavaScript**, il serait impossible de dessiner : c'est le seul cas de l'application où il est indispensable.

---

## 12. La signature obligatoire avant tout le reste : `src/EventSubscriber/SignatureObligatoireSubscriber.php`

```php
<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignatureObligatoireSubscriber implements EventSubscriberInterface
{
    private const ROUTES_AUTORISEES = ['signature', 'signature_enregistrer', 'connexion', 'deconnexion', 'inscription'];

    public function __construct(private Security $security, private UrlGeneratorInterface $urls)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'verifier'];
    }

    public function verifier(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof Utilisateur || $utilisateur->getSignature() !== null) {
            return;
        }

        $route = (string) $event->getRequest()->attributes->get('_route');
        if ($route === '' || str_starts_with($route, '_') || in_array($route, self::ROUTES_AUTORISEES, true)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urls->generate('signature')));
    }
}
```

- Un **subscriber** (abonné) est une classe qui réagit à des **événements** de Symfony. Ici, il s'abonne à `KernelEvents::REQUEST` : il est appelé au début de **chaque** page demandée, avant le contrôleur.
- La méthode `verifier()` laisse passer la page dans ces cas :
  - ce n'est pas la page principale (une sous-requête interne de Symfony) ;
  - personne n'est connecté, ou la personne a déjà une signature ;
  - la page fait partie des pages autorisées (signature, connexion, déconnexion, création de compte), ou c'est une page technique de Symfony, dont le nom commence par `_`.
- Dans tous les autres cas, il remplace la réponse par une redirection vers `/ma-signature`.
- **Pourquoi un subscriber ?** Pour ne pas répéter la même vérification au début de chaque contrôleur. La règle est écrite **une seule fois** et s'applique à toutes les pages, y compris celles des prochaines étapes.

---

## 13. La mise en page : personne connectée et menus

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
                    <span>{{ app.user.roleLibelle }} · {{ app.user.service }}</span>
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

- `app.user` est la personne connectée (un objet `Utilisateur`). On affiche ses initiales, son nom, son rôle et son service en bas de la barre latérale, avec le lien « Quitter » vers `/deconnexion`.
- `{% if is_granted('ROLE_ADMIN') %}` n'affiche le menu Administration qu'à l'administrateur. Ce n'est qu'un confort d'affichage : la vraie protection est la règle `access_control` de `security.yaml`. Un non-administrateur qui tape l'adresse `/utilisateurs` reçoit une erreur 403 (accès refusé).
- `{% block javascripts %}` permet à une page d'ajouter son propre script, comme `inscription.js` ou `signature.js`. Chaque page ne charge que ce dont elle a besoin.

Le CSS ajouté (fin de `public/css/app.css`) habille ces nouveaux éléments : la carte centrée des pages simples, les champs et messages d'erreur du formulaire, les étapes, le cadre de signature et les vignettes. Il suit les mêmes principes que celui de l'étape 1.

---

## 14. Les nouvelles migrations SQL

**La table des signatures** (`migrations/Version20261123014223.php`) :

```php
public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE signature_reference (id INT AUTO_INCREMENT NOT NULL, image_chiffree LONGTEXT NOT NULL, traits_chiffres LONGTEXT NOT NULL, essai_retenu SMALLINT NOT NULL, cree_le DATETIME NOT NULL, utilisateur_id INT NOT NULL, UNIQUE INDEX UNIQ_1276A68FFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE signature_reference ADD CONSTRAINT FK_1276A68FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
    }
```

- `UNIQUE INDEX ... (utilisateur_id)` : un utilisateur ne peut avoir qu'**une** signature de référence. C'est la traduction SQL du `OneToOne`.
- `LONGTEXT` pour l'image et les traits chiffrés, qui peuvent faire plusieurs dizaines de kilo-octets.

**La correction du lien vers le N+1** (`migrations/Version20261123014252.php`) :

```php
public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY `FK_1D1C63B331B7BDD7`');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B331B7BDD7 FOREIGN KEY (n1_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }
```

**Pourquoi cette correction ?** En relançant `composer base` une deuxième fois, le vidage de la table `utilisateur` échouait. MySQL refusait de supprimer un N+1 tant que d'autres utilisateurs pointaient vers lui (clé étrangère `n1_id`). Avec `ON DELETE SET NULL`, si un N+1 est supprimé, la colonne `n1_id` de ses collaborateurs est simplement remise à vide.

Dans l'entité, cela correspond à une seule ligne ajoutée au-dessus de `$n1` :

```php
#[ORM\JoinColumn(onDelete: 'SET NULL')]
```

**À retenir** : quand on modifie une entité, on relance `php bin/console make:migration`. Doctrine écrit seulement le SQL de la **différence** (ici, supprimer puis recréer la clé étrangère). `doctrine:migrations:migrate` l'applique sans toucher aux données.

---

## 15. À retenir pour un autre projet

| Besoin | Outil Symfony | Où |
|---|---|---|
| Connexion par formulaire | `form_login` | `security.yaml` |
| Pages réservées | `access_control` | `security.yaml` |
| Refuser certains comptes | Un *user checker* | `src/Security/` |
| Chiffrer un mot de passe | `UserPasswordHasherInterface` | Contrôleur ou fixtures |
| Connecter quelqu'un depuis le code | `Security::login()` | Contrôleur |
| Protéger un formulaire | `csrf_token()` + `isCsrfTokenValid()` | Gabarit + contrôleur |
| Une règle appliquée à toutes les pages | Un *event subscriber* | `src/EventSubscriber/` |
| Une logique réutilisable (chiffrement, calcul) | Un *service* | `src/Service/` |
| Lire un réglage de `.env` | `#[Autowire('%env(NOM)%')]` | Paramètre du constructeur ou de la méthode |

La règle d'or de cette étape : **tout ce qui vient du navigateur est vérifié par le serveur** (N+1 du même département, domaine de l'e-mail, format des essais de signature). Le JavaScript n'est là que pour le confort.

---

**Étape suivante (4)** : la création de la FED par le demandeur, sur le modèle du formulaire Word 05SUP02V03.

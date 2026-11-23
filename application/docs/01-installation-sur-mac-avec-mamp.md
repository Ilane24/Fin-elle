# Installer et lancer l'application sur votre Mac (MAMP)

Ce guide part de zéro. À la fin, le site tourne sur votre Mac à l'adresse <http://localhost:8000>, avec sa base de données MySQL.

Vous allez utiliser trois outils :

| Outil | À quoi il sert |
|---|---|
| **MAMP** | Fournit PHP (le langage du site) et MySQL (la base de données) |
| **Terminal** | L'application « Terminal » du Mac (Applications → Utilitaires), pour taper les commandes |
| **Composer** | Télécharge Symfony et les bibliothèques utilisées par le projet |

> Dans ce guide, chaque commande est à taper dans le Terminal, puis à valider avec la touche Entrée. Le texte qui suit `#` sur une ligne est un commentaire : ne le tapez pas.

---

## Étape 1 — Vérifier la version de PHP de MAMP

Symfony 7.4 demande **PHP 8.2 ou plus récent**.

1. Ouvrez MAMP.
2. Allez dans **MAMP → Preferences (ou Settings) → PHP**, et regardez les versions proposées.
3. Choisissez la plus récente (8.2 ou plus).

Vérifiez aussi dans le Terminal quelles versions de PHP sont installées par MAMP :

```bash
ls /Applications/MAMP/bin/php/
```

Vous obtenez une liste comme `php8.1.13  php8.2.0  php8.3.1`. **Notez le nom exact de la version 8.2 ou plus** : vous en aurez besoin à l'étape suivante.

> **Si vous n'avez aucune version 8.2 ou plus** (cela arrive avec une ancienne version de macOS et de MAMP), dites-le-moi. Je passerai le projet sur Symfony 6.4, qui fonctionne avec PHP 8.1. Le code reste presque identique.

---

## Étape 2 — Utiliser le PHP de MAMP dans le Terminal

Par défaut, le Terminal ne connaît pas le PHP de MAMP. On lui indique où il se trouve. Remplacez `php8.2.0` par le nom noté à l'étape 1 :

```bash
echo 'export PATH=/Applications/MAMP/bin/php/php8.2.0/bin:$PATH' >> ~/.zshrc
source ~/.zshrc
```

> Sur un macOS plus ancien qui utilise le shell `bash` (le Terminal affiche « bash » dans son titre), remplacez `~/.zshrc` par `~/.bash_profile` dans les deux lignes.

Vérifiez :

```bash
php -v
```

La première ligne doit afficher `PHP 8.2` (ou plus).

---

## Étape 3 — Installer Composer

Tapez ces quatre commandes, une par une :

```bash
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

Rendez Composer utilisable partout (votre mot de passe de session Mac vous sera demandé ; il ne s'affiche pas quand vous le tapez, c'est normal) :

```bash
sudo mkdir -p /usr/local/bin
sudo mv composer.phar /usr/local/bin/composer
composer -V
```

La dernière commande doit afficher `Composer version 2...`.

---

## Étape 4 — Télécharger le projet depuis GitHub

**Solution simple (sans Git)**

1. Ouvrez <https://github.com/ilane24/fin-elle> dans votre navigateur.
2. En haut à gauche, dans le menu des branches, choisissez `claude/finelle-cofina-design-spec-qj2cfw`.
3. Cliquez sur le bouton vert **Code** → **Download ZIP**.
4. Décompressez le fichier dans un dossier simple, par exemple `Documents/fin-elle`.

**Solution avec Git** (si Git est installé)

```bash
cd ~/Documents
git clone -b claude/finelle-cofina-design-spec-qj2cfw https://github.com/ilane24/fin-elle.git
```

Dans les deux cas, le projet Symfony se trouve dans le sous-dossier **`application`**. Le dossier `maquette` contient la maquette, qui reste inchangée.

---

## Étape 5 — Installer les bibliothèques du projet

Placez-vous dans le dossier `application` (adaptez le chemin si vous avez décompressé ailleurs) :

```bash
cd ~/Documents/fin-elle/application
composer install
```

Composer lit le fichier `composer.json` et télécharge Symfony et les autres bibliothèques dans un dossier `vendor/`. Cela prend une à deux minutes. Ce dossier n'est pas sur GitHub : chaque ordinateur le télécharge lui-même.

---

## Étape 6 — Démarrer MySQL dans MAMP

1. Dans MAMP, cliquez sur **Start** (ou « Démarrer les serveurs »).
2. Vérifiez les réglages dans **Preferences → Ports** : sur Mac, MySQL utilise par défaut le **port 8889**, avec l'utilisateur **root** et le mot de passe **root**.

Le projet est déjà réglé pour ces valeurs, dans le fichier `application/.env` :

```dotenv
DATABASE_URL="mysql://root:root@127.0.0.1:8889/finelle?serverVersion=8.0.32&charset=utf8mb4"
```

Cette ligne se lit ainsi : « se connecter à MySQL avec l'utilisateur `root`, le mot de passe `root`, sur cet ordinateur (`127.0.0.1`), port `8889`, base `finelle` ».

> **Si vos réglages sont différents** (autre port, autre mot de passe, version de MySQL différente), ne modifiez pas `.env`. Créez à côté un fichier **`.env.local`** contenant seulement la ligne corrigée. Symfony lit `.env.local` en priorité, et ce fichier n'est pas envoyé sur GitHub.
>
> La version de MySQL est affichée sur la page d'accueil de MAMP (WebStart). Mettez-la après `serverVersion=` (par exemple `5.7.39` ou `8.0.35`).

---

## Étape 7 — Créer la base, les tables et les données de démonstration

Toujours dans le dossier `application` :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

| Commande | Ce qu'elle fait |
|---|---|
| `doctrine:database:create` | Crée la base de données vide `finelle` dans MySQL |
| `doctrine:migrations:migrate` | Exécute les scripts SQL du dossier `migrations/` : crée les tables `departement`, `service` et `utilisateur` (répondez `yes` si on vous le demande) |
| `doctrine:fixtures:load` | Vide les tables et les remplit avec les données de démonstration : 6 départements, 10 services et 14 utilisateurs (répondez `yes`) |

Vous pouvez voir les tables dans **phpMyAdmin** : page WebStart de MAMP → Tools → phpMyAdmin → base `finelle`.

---

## Étape 8 — Lancer le site

```bash
php -S localhost:8000 -t public
```

Ouvrez <http://localhost:8000> dans votre navigateur. Vous voyez le tableau de bord, puis la page **Utilisateurs** dans le menu de gauche.

- Le Terminal reste occupé tant que le site tourne : c'est normal. Pour l'arrêter, revenez dans le Terminal et tapez **Ctrl + C**.
- La commande `php -S` utilise le petit serveur web intégré à PHP. Il suffit pour développer. Plus tard, sur le serveur de l'entreprise, on utilisera un vrai serveur web.

---

## Les prochaines fois

Il suffit de :

1. démarrer MAMP (pour MySQL) ;
2. dans le Terminal :

```bash
cd ~/Documents/fin-elle/application
php -S localhost:8000 -t public
```

Quand je vous livre une nouvelle étape :

1. téléchargez de nouveau le ZIP, ou tapez `git pull` si vous avez utilisé Git ;
2. tapez ces deux commandes :

```bash
composer install
php bin/console doctrine:migrations:migrate
```

La seconde crée seulement les nouvelles tables, sans toucher aux données existantes.

---

## En cas de problème

| Message | Cause probable | Solution |
|---|---|---|
| `command not found: php` ou une version de PHP inférieure à 8.2 | Le Terminal n'utilise pas le PHP de MAMP | Refaire l'étape 2, puis fermer et rouvrir le Terminal |
| `could not find driver` | Même cause : PHP sans l'extension MySQL | Refaire l'étape 2 |
| `Connection refused` | MySQL n'est pas démarré, ou ce n'est pas le bon port | Démarrer MAMP ; vérifier le port (8889) ; créer un `.env.local` si besoin |
| `Access denied for user 'root'` | Mauvais mot de passe MySQL | Mettre le bon mot de passe dans un `.env.local` |
| `Your requirements could not be resolved` (avec `composer install`) | PHP trop ancien | Vérifier `php -v` (8.2 minimum), sinon me prévenir |
| La page affiche une erreur Symfony | Une étape a été sautée | Refaire les étapes 5 à 7 ; si l'erreur persiste, copiez-moi le message |

# Fin'Elle · Application de gestion des achats (Symfony)

Application web du circuit des dépenses (FED, menue dépense, bon de commande), réalisée avec **Symfony 7.4**, **Twig**, du **CSS classique**, un peu de **JavaScript** et une base **MySQL**.

La maquette d'origine se trouve dans le dossier `../maquette` et n'est pas modifiée.

## Documentation

| Fichier | Contenu |
|---|---|
| `docs/01-installation-sur-mac-avec-mamp.md` | Installer et lancer le site sur un Mac avec MAMP, pas à pas |
| `docs/02-etapes-1-et-2-expliquees.md` | Le code des étapes 1 et 2, fichier par fichier, avec ses explications |

## Démarrage rapide

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php -S localhost:8000 -t public
```

Comptes de démonstration : `prenom.nom@cofina-demo.com`, mot de passe `demo2026` (la connexion arrive à l'étape 3).

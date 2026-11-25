# Fin'Elle · Application de gestion des achats (Symfony)

Application web du circuit des dépenses (FED, menue dépense, bon de commande), réalisée avec **Symfony 7.4**, **Twig**, du **CSS classique**, un peu de **JavaScript** et une base **MySQL**.

La maquette d'origine se trouve dans le dossier `../maquette` et n'est pas modifiée.

## Documentation

| Fichier | Contenu |
|---|---|
| `docs/01-installation-sur-mac-avec-mamp.md` | Installer et lancer le site sur un Mac avec MAMP, pas à pas |
| `docs/02-etapes-1-et-2-expliquees.md` | Étapes 1 et 2 : base du projet et base de données, code et explications |
| `docs/03-etape-3-expliquee.md` | Étape 3 : connexion, création de compte et signature, code et explications |
| `docs/04-changement-departement-explique.md` | Changement : le département remplace le service, le matricule disparaît |

## Démarrage rapide

```bash
composer install   # télécharge Symfony et les bibliothèques
composer base      # crée la base, les tables et les données de démonstration
composer start     # démarre le site sur http://localhost:8000 (Ctrl + C pour l'arrêter)
```

Comptes de démonstration : `prenom.nom@cofina-demo.com`, mot de passe `demo2026` (par exemple `fatou.diallo@cofina-demo.com`). L'administratrice est `mireille.gbagbo@cofina-demo.com`. À la première connexion, chaque compte enregistre sa signature.

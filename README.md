# Fin'Elle – Maquette

Maquette interactive (HTML/CSS/JS, sans dépendance) de l'outil Fin'Elle / Cofina.
Ouvrir `maquette/index.html` dans un navigateur.

**Démo** : e-mail `prenom.nom@cofina-demo.com`, mot de passe de 8 caractères minimum, rôle au choix.

## Charte
- Fond blanc, cartes gris clair, texte gris foncé, rose en accent (boutons, étape active, badges).
- Rose indicatif `#D6336C` : toutes les couleurs sont des variables CSS en tête de fichier (`:root`), à remplacer par la charte officielle Fin'Elle/Cofina.
- Style sobre : coins arrondis, ombres légères, icônes simples, logo en haut à gauche.
- Micro-animations au survol (150–200 ms) : élévation des boutons et cartes, flèches qui glissent, barre d'accent sur la ligne de tableau et le menu, étape active pulsée. Désactivées si l'utilisateur a choisi « réduire les animations ».

## Écrans
Connexion · Tableau de bord · Dossiers (filtres) · Détail dossier (étapes) · Nouvelle demande (assistant) · Utilisateurs & rôles (matrice des droits) · Journal d'audit.

## Règles
- Langue française ; montants en FCFA ; dates `JJ/MM/AAAA`, heures `HH:MM`.
- Ordinateur d'abord ; sur mobile, navigation repliable et tableaux défilants.
- Sécurité simulée : e-mail restreint au domaine (`ALLOWED_DOMAIN`), droits par rôle (`ROLES`), journal d'audit alimenté par les actions.
- Données fictives uniquement.

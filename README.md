# Fin'Elle – Maquette

Maquette interactive (HTML/CSS/JS, sans dépendance) du circuit de **fiche d'engagement de dépenses** (formulaire 05SUP02V03).
Ouvrir `maquette/index.html` dans un navigateur, puis cliquer sur « Se connecter » (champs pré-remplis).

## Circuit
1. Le **demandeur** remplit la fiche (date, demandeur, fonction, service, objet, référence, pro-forma oui/non, montant) et la **signe**.
2. La fiche est transmise à son **N+1**, qui reçoit une notification.
3. Le N+1 **valide en signant**, ou **rejette avec un commentaire** obligatoire.
4. Le demandeur est notifié ; en cas de rejet, il voit le commentaire et peut **corriger et renvoyer** la fiche.

Le sélecteur « Profil (démo) » en haut permet de passer de Fatou Diallo (demandeur) à Serge Kouadio (N+1) ou Mireille Gbagbo (administratrice) pour tester le circuit.

## Charte
- Fond blanc, cartes gris clair, texte gris foncé, rose en accent (boutons, étape active, badges).
- Rose indicatif `#D6336C` : toutes les couleurs sont des variables CSS (`:root`), à remplacer par la charte officielle Fin'Elle/Cofina.
- Micro-animations au survol (150–200 ms), désactivées si l'utilisateur a choisi « réduire les animations ».

## Écrans
Connexion · Tableau de bord · Nouvelle demande (fiche + signature) · Demandes · À valider (N+1) · Détail d'une demande (fiche, signatures, historique) · Utilisateurs & rôles (avec N+1) · Journal d'audit.

## Règles
- Français ; montants en FCFA ; dates `JJ/MM/AAAA`, heures `HH:MM`.
- Ordinateur d'abord ; sur mobile, navigation repliable et tableaux défilants.
- Sécurité simulée : e-mail restreint au domaine (`ALLOWED_DOMAIN`), droits par rôle (`ROLES`), journal d'audit.
- Données fictives uniquement, conservées en mémoire (réinitialisées au rechargement).

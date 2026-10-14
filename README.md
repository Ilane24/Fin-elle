# Fin'Elle – Maquette

Maquette interactive (HTML/CSS/JS, sans dépendance) du circuit de **fiche d'engagement de dépenses** (formulaire 05SUP02V03).
Ouvrir `maquette/index.html` dans un navigateur, puis cliquer sur « Se connecter » (champs pré-remplis).

## Comptes
- **Connexion** (e-mail du domaine + mot de passe) ou **création de compte** : identification (nom, prénom, matricule, téléphone, e-mail professionnel), compte (fonction, service, N+1, mot de passe) et option **compte menues dépenses**.
- Une **menue dépense** n'est possible que si le demandeur a un compte menues dépenses ; l'administrateur peut l'ouvrir ou le fermer depuis « Utilisateurs & rôles ».

## PDF
Sur chaque demande, le bouton **PDF** télécharge la FED dans son état actuel (sections remplies, signatures, historique), avec un filigrane « EN COURS DE VALIDATION » tant qu'elle n'est pas validée. Accessible à tout utilisateur qui peut consulter la demande.

## Circuit
1. Le **demandeur** remplit la fiche (date, demandeur, fonction, service, objet, référence, pro-forma oui/non, montant) et la **signe**.
2. La fiche est transmise à son **N+1**, qui reçoit une notification.
3. Le N+1 **valide en signant**, ou **rejette avec un commentaire** obligatoire (le demandeur peut alors **corriger et renvoyer**).
4. Une fois validée, la fiche signée part en **lecture seule** chez le **RAF/HF** (Responsable Achats et Moyens Généraux).
5. Le RAF/HF complète sa partie « Validation (1) de la demande par RAF/HF » : case « Montant Achat » (< 50 000, > 50 000, > 100 000, > 1 500 000, < 1 500 000), proformas 1 à 3 (fournisseur, réf. N°, montant), réserve, date, puis **signe** ; ou **retourne la fiche au N+1** avec un commentaire si elle est mal remplie.
6. La fiche part ensuite chez le **CFO** (Directeur Financier et Comptable) pour la « Validation (2) » : proforma retenue (parmi celles du RAF/HF), montant validé en chiffre, montant en lettre (rempli automatiquement), réserve, date et **signature** ; ou **retour au RAF/HF** avec commentaire (sa partie reste pré-remplie).
7. Chaque décision est notifiée et tracée (historique de la demande et journal d'audit).

Le sélecteur « Profil (démo) » en haut permet de passer de Fatou Diallo (demandeur), Serge Kouadio (N+1), Carine Toro (RAF/HF), Régis Kouamé (CFO) ou Mireille Gbagbo (administratrice) pour tester le circuit.

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

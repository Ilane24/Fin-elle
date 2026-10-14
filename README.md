# Fin'Elle – Maquette

Maquette interactive (HTML/CSS/JS, sans dépendance) du circuit de **fiche d'engagement de dépenses** (formulaire 05SUP02V03).
Ouvrir `maquette/index.html` dans un navigateur, puis cliquer sur « Se connecter » (champs pré-remplis).

## Comptes
- **Connexion** (e-mail du domaine + mot de passe) ou **création de compte** : identification (nom, prénom, matricule, téléphone, e-mail professionnel), compte (fonction, service, N+1, mot de passe) et option **compte menues dépenses**.
- Une **menue dépense** n'est possible que si le demandeur a un compte menues dépenses ; l'administrateur peut l'ouvrir ou le fermer depuis « Utilisateurs & rôles ».

## PDF
Sur chaque demande, le bouton **PDF** télécharge la FED dans son état actuel (sections remplies, signatures, historique), avec un filigrane « EN COURS DE VALIDATION » tant qu'elle n'est pas validée. Accessible à tout utilisateur qui peut consulter la demande.

## Circuit
Toute demande passe **obligatoirement par la FED** (fiche d'engagement de dépenses, 05SUP02V03). En « Nouvelle demande », le demandeur choisit le **type de demande** :
- **Bon de commande** : quand le prestataire ou fournisseur est défini et connu (fournisseur obligatoire) ;
- **Menue dépense** : uniquement avec un compte menues dépenses.

1. **FED** : le demandeur remplit et signe → **N+1** (valide en signant ou rejette avec commentaire) → **RAF/HF** (montant achat, proformas, réserve, signature, ou retour au N+1) → **CFO** (proforma retenue, montant en chiffre et en lettre, réserve, signature, ou retour au RAF/HF).
2. Puis, selon le type :
   - **Menue dépense** : fiche de menues dépenses (Version 01) signée par le **RAF/HF**, la **Direction Centrale**, la **Direction Générale si le montant est ≥ 150 001 FCFA**, puis visée par la **Comptabilité** (référence N°, évolution budgétaire, imputation, désignation, pièces justificatives, visa). Chaque signataire peut rejeter avec un commentaire.
   - **Bon de commande** : émis et signé par le **RAF/HF** au fournisseur.
3. Chaque décision est notifiée et tracée (historique de la demande et journal d'audit).

Profils de démonstration (sélecteur « Profil (démo) ») : Fatou Diallo (demandeur, compte menues dépenses), Awa Ndiaye (demandeur sans compte), Serge Kouadio (N+1), Carine Toro (RAF/HF), Régis Kouamé (CFO / Direction Centrale), Mariam Yenita Bamba (DG), Paul Yao (Comptabilité), Mireille Gbagbo (administratrice).

## Charte
- Charte officielle Fin'Elle (filiale du Groupe Cofina) : **rouge #DC092F** et **gris #646363** du logo, fond blanc, cartes gris clair.
- Logo officiel en haut à gauche et dans l'en-tête des PDF (`maquette/assets/`, extrait de la fiche de menues dépenses).
- Toutes les couleurs sont des variables CSS (`:root`) ; un rouge plus sombre est réservé aux rejets et erreurs pour ne pas se confondre avec la marque.
- Micro-animations au survol (150–200 ms), désactivées si l'utilisateur a choisi « réduire les animations ».

## Écrans
Connexion · Tableau de bord · Nouvelle demande (fiche + signature) · Demandes · À valider (N+1) · Détail d'une demande (fiche, signatures, historique) · Utilisateurs & rôles (avec N+1) · Journal d'audit.

## Règles
- Français ; montants en FCFA ; dates `JJ/MM/AAAA`, heures `HH:MM`.
- Ordinateur d'abord ; sur mobile, navigation repliable et tableaux défilants.
- Sécurité simulée : e-mail restreint au domaine (`ALLOWED_DOMAIN`), droits par rôle (`ROLES`), journal d'audit.
- Données fictives uniquement, conservées en mémoire (réinitialisées au rechargement).

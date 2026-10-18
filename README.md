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
- **Menue dépense** : uniquement avec un compte menues dépenses et **jusqu'à 250 000 FCFA** (plafond) ;
- **Bon de commande** : obligatoire au-delà de 250 000 FCFA (la menue dépense est alors bloquée automatiquement à la saisie ; si le CFO valide un montant supérieur au plafond, la demande bascule d'elle-même en bon de commande).

1. **FED** : le demandeur remplit et signe → **N+1** (valide en signant ou rejette avec commentaire) → **RAF/HF** (montant achat, proformas, réserve, signature, ou retour au N+1) → **CFO** (proforma retenue, montant en chiffre et en lettre, réserve, signature, ou retour au RAF/HF).
2. Puis, selon le type :
   - **Bon de commande** (05SUP03V03) : établi par la **Comptabilité** (fournisseur, lignes, montant à payer, montant HT en lettres, conditions) et **délivré à Facilities** ; les cases RAMG, CFO et Fournisseur sont à signer sur le bon imprimé.
   - **Menue dépense** : fiche de menues dépenses (Version 01) signée par le **RAF/HF**, la **Direction Centrale**, la **Direction Générale si le montant est ≥ 150 001 FCFA**, puis visée par la **Comptabilité** (référence N°, évolution budgétaire, imputation, désignation, pièces justificatives, visa). Chaque signataire peut rejeter avec un commentaire.
3. **Achat · Facilities (72 h)** : toutes les personnes du service **FACILITIES** (sauf pour leurs propres demandes) tirent la FED en PDF (sans filigrane) et doivent effectuer l'achat **sous 72 h** :
   - **Bon de commande** : le délai démarre à la délivrance du bon par la Comptabilité ; la demande est clôturée quand Facilities confirme l'achat ;
   - **Menue dépense** : le délai démarre au décaissement, c'est-à-dire quand la fiche de menues dépenses est signée (RAF/HF, Direction Centrale, et DG si ≥ 150 001 FCFA).
   L'écran « Achats à traiter » classe les achats par échéance avec un compte à rebours (reste X h, en retard de X h) ; Facilities confirme l'achat (fournisseur, montant payé, facture).
4. Chaque décision est notifiée et tracée (historique de la demande et journal d'audit).

Profils de démonstration (sélecteur « Profil (démo) ») : Fatou Diallo (demandeur, compte menues dépenses), Awa Ndiaye (demandeur sans compte), Serge Kouadio (N+1), Carine Toro (RAF/HF), Régis Kouamé (CFO / Direction Centrale), Mariam Yenita Bamba (DG), Paul Yao (Comptabilité), Mireille Gbagbo (administratrice).

## Formulaires par niveau et signature électronique
- **Chaque niveau de validation a son propre formulaire**, rempli dans le dossier : partie Demandeur de la FED, validation N+1 (décision, commentaire, signature), validation (1) RAF/HF, validation (2) CFO, formulaire de chaque signataire de la fiche de menues dépenses (RAF/HF, Direction Centrale, DG), visa Comptabilité, bon de commande (Comptabilité), confirmation d'achat (Facilities).
- **Signature électronique** : signature manuscrite à l'écran + attestation obligatoire (« Je certifie l'exactitude des informations… et je signe électroniquement »). Le contenu du formulaire, le signataire, l'horodatage et la version sont scellés par une **empreinte SHA-256**.
- **Verrouillage** : un formulaire signé n'est plus modifiable ; l'empreinte est revérifiée à chaque affichage (« intégrité vérifiée », ou alerte « modifié après signature »). Le registre « Formulaires signés » liste les formulaires en vigueur.
- **Versions** : après un retour (RAF/HF → N+1, CFO → RAF/HF) ou un rejet, les formulaires signés concernés sont **archivés, toujours verrouillés**, et le niveau signe une nouvelle version (v2, v3…).
- Le PDF reprend l'empreinte sous chaque signature.

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

# Fin'Elle – Maquette

Maquette interactive (HTML/CSS/JS, sans dépendance) du circuit de **fiche d'engagement de dépenses** (formulaire 05SUP02V03).
Ouvrir `maquette/index.html` dans un navigateur, puis cliquer sur « Se connecter » (champs pré-remplis).

## Comptes
- **Connexion** (e-mail du domaine + mot de passe) ou **création de compte** : identification (nom, prénom, matricule, téléphone, e-mail professionnel), compte (fonction, **département**, service, N+1, mot de passe) et option **compte menues dépenses**.
- **Départements** : chaque utilisateur appartient à un département, choisi à l'inscription. Le service et le N+1 proposés sont ceux de ce département : un N+1 d'un autre département ne peut pas signer la FED (contrôlé à l'inscription, à l'envoi de la FED et à la signature du N+1 ; toute tentative est tracée).
- **Signature de référence (étape obligatoire)** : juste après la création du compte, et à chaque connexion tant qu'elle n'est pas faite, un assistant pas à pas guide l'utilisateur : **Consignes → Essai 1 → Essai 2 → Essai 3 → Enregistrement**. Chaque essai se fait dans un grand espace de tracé (souris ou stylet), avec un bouton « Effacer ». La seule condition est que l'essai ne soit pas vide. Les vignettes des essais déjà faits permettent de revenir en refaire un. Une fois les trois essais faits, le système garde automatiquement celui qui ressemble le plus aux deux autres (comparaison après normalisation de la taille et de la position : forme, tracé, proportions) et l'enregistre, sans seuil ni refus. La référence est stockée en PNG transparent avec ses traits vectoriels, chiffrée en AES-256-GCM et liée à l'identifiant de l'utilisateur. Les comptes de démonstration ont une signature fictive.
- Une **menue dépense** n'est possible que si le demandeur a un compte menues dépenses ; l'administrateur peut l'ouvrir ou le fermer depuis « Utilisateurs & rôles ».

## PDF
Tant que la demande est en cours de validation, le bouton **PDF** télécharge la FED dans son état actuel (sections remplies, signatures, historique), avec un filigrane « EN COURS DE VALIDATION ». Il est accessible à tout utilisateur qui peut consulter la demande. Une fois toutes les étapes de validation franchies, seules les personnes du service FACILITIES peuvent tirer la **FED finale** (sans filigrane), sauf pour leurs propres demandes ; les autres voient « FED finale réservée à Facilities ».

## Circuit
Toute demande passe **obligatoirement par la FED** (fiche d'engagement de dépenses, 05SUP02V03). En « Nouvelle demande », le demandeur choisit le **type de demande** :
- **Menue dépense** : uniquement avec un compte menues dépenses et **jusqu'à 250 000 FCFA** (plafond) ;
- **Bon de commande** : obligatoire au-delà de 250 000 FCFA (la menue dépense est alors bloquée automatiquement à la saisie ; si le CFO valide un montant supérieur au plafond, la demande bascule d'elle-même en bon de commande).

1. **FED** (disposition du formulaire Word 05SUP02V03 : Demandeur et signatures Demandeur / Manager, Validation (1) RAF/HF, Validation (2) CFO) : le demandeur remplit toute la FED, c'est-à-dire l'objet, la pro-forma jointe, les précisions et, dans le bloc Validation (1), les proformas 1 à 3 (fournisseur et montant). Le système remplit automatiquement la date, l'identité, le service, la fonction, la référence de la FED, les **références des proformas** (`FED-AAAA-NNNN-PF1`…), le montant estimé (proforma la moins-disante) et la case « Montant achat ». → **N+1** du même département : la FED arrive complète, en lecture seule ; « Valider » (signature enregistrée apposée) ou « Rejeter » avec motif. → **RAF/HF** : réserve facultative, « Valider et signer » ou retour au N+1. → **CFO** : choisit la proforma retenue (la moins-disante est pré-sélectionnée), confirme le montant en chiffres et en lettres, « Valider et signer » ou retour au RAF/HF.
2. Puis, selon le type :
   - **Bon de commande** (05SUP03V03) : établi par la **Comptabilité** (fournisseur, lignes, montant à payer, montant HT en lettres, conditions) et **délivré à Facilities** ; les cases RAMG, CFO et Fournisseur sont à signer sur le bon imprimé.
   - **Menue dépense** : fiche de menues dépenses (Version 01) signée par le **RAF/HF**, la **Direction Centrale**, la **Direction Générale si le montant est ≥ 150 001 FCFA**, puis visée par la **Comptabilité** (référence N°, évolution budgétaire, imputation, désignation, pièces justificatives, visa). Chaque signataire peut rejeter avec un commentaire.
3. **Achat · Facilities (72 h)** : toutes les personnes du service **FACILITIES** (sauf pour leurs propres demandes) tirent la FED en PDF (sans filigrane) et doivent effectuer l'achat **sous 72 h** :
   - **Bon de commande** : le délai démarre à la délivrance du bon par la Comptabilité ; la demande est clôturée quand Facilities confirme l'achat ;
   - **Menue dépense** : le délai démarre au décaissement, c'est-à-dire quand la fiche de menues dépenses est signée (RAF/HF, Direction Centrale, et DG si ≥ 150 001 FCFA).
   L'écran « Achats à traiter » classe les achats par échéance avec un compte à rebours (reste X h, en retard de X h) ; Facilities confirme l'achat (fournisseur, montant payé, facture).
4. Chaque décision est notifiée et tracée (historique de la demande et journal d'audit).

Profils de démonstration (sélecteur « Profil (démo) ») : Fatou Diallo (demandeur, compte menues dépenses), Awa Ndiaye (demandeur sans compte), Serge Kouadio (N+1), Carine Toro (RAF/HF), Régis Kouamé (CFO / Direction Centrale), Mariam Yenita Bamba (DG), Paul Yao (Comptabilité), Mireille Gbagbo (administratrice), ainsi que les responsables Aminata Koné (RH), Yves Brou (Informatique), Nadia Cissé (Commercial) et Éric Konan (demandeur, Commercial).

## Formulaires par niveau et signature électronique
- **Une seule signature par personne et par dossier** : si le signataire attendu a déjà validé et signé la même FED, sa validation et sa signature sont reprises automatiquement pour les étapes suivantes qui ne demandent aucune saisie. Cela couvre : la validation (1) du RAF/HF, la validation (2) du CFO (pro-forma la moins-disante et son montant), et les signatures RAF/HF, Direction Centrale et DG de la fiche de menues dépenses. Par exemple, après la validation du CFO, la fiche de menues dépenses passe directement à la DG, car le RAF/HF et la Direction Centrale ont déjà signé. Le visa de la Comptabilité, le bon de commande et l'achat restent manuels, ainsi que tout passage après un retour ou un rejet. Chaque reprise est notée dans l'historique et le journal d'audit.
- **Signature en un clic, pour tous** : du demandeur à l'administrateur, chaque niveau (demandeur, N+1, RAF/HF, CFO, hiérarchie des menues dépenses, Comptabilité, Facilities) valide d'un clic. La signature enregistrée à la création du compte est apposée automatiquement à l'emplacement prévu : plus de tracé à la main ni de case à cocher, le bouton de validation vaut attestation. La signature est déchiffrée en mémoire pour la session et reste chiffrée au repos.
- **Reprise automatique des informations de la FED** : ce que le demandeur saisit apparaît à son emplacement sur la FED dès l'étape N+1, en lecture seule pour tous les niveaux. Le bon de commande reprend le fournisseur, la référence, la désignation et le montant, et le visa de la Comptabilité reprend la désignation.
- **Chaque niveau de validation a son propre formulaire**, rempli dans le dossier : partie Demandeur de la FED, validation N+1 (décision, commentaire, signature), validation (1) RAF/HF, validation (2) CFO, formulaire de chaque signataire de la fiche de menues dépenses (RAF/HF, Direction Centrale, DG), visa Comptabilité, bon de commande (Comptabilité), confirmation d'achat (Facilities).
- **Signature électronique** : signature manuscrite à l'écran + attestation obligatoire (« Je certifie l'exactitude des informations… et je signe électroniquement »). Le contenu du formulaire, le signataire, l'horodatage et la version sont scellés par une **empreinte SHA-256**.
- **Verrouillage** : un formulaire signé n'est plus modifiable ; l'empreinte est revérifiée à chaque affichage. L'utilisateur voit seulement « Signé et verrouillé », ou l'alerte « Modifié après signature ! ». L'empreinte reste consultable au survol, dans le PDF et dans le journal d'audit. Le registre « Formulaires signés » liste les formulaires en vigueur.
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

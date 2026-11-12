# 23. Attestations

Menu : **Attestations > Attestations délivrées**, **Types d’attestation**, **Paramètres > Paramètres des attestations**. Les attestations sont délivrées avec le bouton **Délivrer une attestation** de l’écran du dossier étudiant (**Étudiants et personnels > Dossiers étudiants > Afficher**). Les étudiants téléchargent les PDF de leurs propres attestations dans le portail sous **Attestations**. Chacun peut vérifier une attestation sur la page `/verify/{code}`.

| Autorisation | Ce qu’elle permet |
|---|---|
| `Délivrer, régénérer et révoquer des attestations` | Délivrer, régénérer, révoquer, consulter les attestations délivrées (dans le périmètre de consultation des dossiers étudiants) |
| `Gérer les types d’attestation, les modèles et les paramètres` | Types et leurs modèles par langue, format du numéro de série, signataires |

Rôles par défaut : Responsable des attestations délivre, Scolarité dispose des deux, Affaires pédagogiques gère les types et les modèles.

## Différences avec l’ancien système

- **Registre des attestations délivrées.** L’ancien système (YUSTIC) imprimait directement depuis l’écran (`aca3/cti_*`), et son registre (`SRCERT`) ne servait qu’à générer des numéros ; il n’indiquait donc ni qui avait délivré une attestation ni si elle avait été révoquée. Le nouveau système enregistre pour chaque délivrance le numéro de série, le code de vérification, le membre du personnel, l’objet, le nombre d’exemplaires, le fichier PDF et la somme de contrôle, et une attestation révoquée conserve sa ligne.
- **Vérification.** L’ancien système n’en avait pas. Chaque attestation porte un code de vérification et un QR code, et une page publique indique qu’elle a été délivrée, son type, sa date de délivrance et le titulaire masqué (première et dernière lettre du nom et du prénom, 4 premiers chiffres du numéro d’étudiant).
- **Modèles.** L’ancien système disposait d’un script PDF distinct par type et par langue. Le nouveau système permet de modifier à l’écran un modèle par langue (titre, corps, pied de page, signataire) pour chaque type.
- **Blocages.** L’ancien système délivrait les attestations sans tenir compte de la liste des refus d’inscription. Le nouveau système ne délivre pas d’attestation à un étudiant faisant l’objet d’un blocage d’attestations ([chapitre 20](20-registration.md)).
- Les frais ne sont pas enregistrés (l’ancien système n’enregistrait que le montant).
- Les étudiants ne peuvent pas faire de demande en ligne (plan de portage 17.5). C’est le personnel qui délivre les attestations.

## Types et modèles

**Types d’attestation** : code (utilisé dans le numéro de série), nom (3 langues), destinataires (tout dossier étudiant / étudiants / diplômés), inclut les notes, inclut la photo, actif, ordre. Le profil université coréenne fournit trois types.

| Code | Type | Destinataires | Remarque |
|---|---|---|---|
| `ENR` | Certificat de scolarité | Étudiants (non diplômés) | |
| `GRD` | Attestation de réussite au diplôme | Diplômés | Date d’obtention du diplôme, numéro de diplôme |
| `TRN` | Relevé de notes | Tout dossier étudiant | Tableau des notes définitives par semestre, moyenne cumulée et crédits acquis |

**Modèles** (en bas de l’écran de modification du type) : un par langue. Titre, corps (paragraphes séparés par une ligne vide), pied de page, signataire. Le corps utilise des variables entre accolades.

| Variable | Valeur |
|---|---|
| `{institution}` | Nom de l’établissement (dans cette langue) |
| `{student_name}`, `{student_number}` | Nom (sous la forme de cette langue, à défaut l’orthographe latine), numéro d’étudiant |
| `{program}`, `{org_unit}` | Formation, unité organisationnelle |
| `{admission_date}`, `{graduation_date}`, `{degree_number}` | Date d’admission, date d’obtention du diplôme, numéro de diplôme |
| `{status}`, `{year_level}` | Statut de l’étudiant, année d’études |
| `{birth_date}`, `{gender}`, `{nationality}` | Date de naissance, sexe, nationalité |
| `{issue_date}`, `{serial_number}`, `{purpose}`, `{copies}` | Date de délivrance, numéro de série, objet, nombre d’exemplaires |

Les dates sont imprimées au format de cette langue. Une valeur manquante (par ex. la date d’obtention du diplôme) est imprimée vide. Un tableau d’informations personnelles (nom, numéro d’étudiant, date de naissance, sexe, nationalité, formation, date d’admission, puis la date d’obtention du diplôme pour les diplômés ou, à défaut, le statut et l’année d’études) est ajouté automatiquement au-dessus du corps.

## Paramètres

**Paramètres > Paramètres des attestations**

- **Format du numéro de série** : par défaut `{TYPE}-{YYYY}-{SEQ:6}` → `ENR-2026-000001`. `{SEQ:n}` est obligatoire. La séquence est comptée séparément pour chaque préfixe distinct. `{TYPE}-{SEQ:6}` compte par type sur toutes les années, `{YYYY}{SEQ:5}` par année sur tous les types. Les délivrances simultanées n’entrent jamais en collision (le compteur est incrémenté de façon atomique dans la base de données).
- **Durée de validité de la vérification (jours)** : 0 signifie toujours. Passé ce délai, la page de vérification répond « expirée ». La délivrance elle-même reste dans le registre.
- **Signataire par langue** : le nom imprimé au-dessus du sceau (« Le Président, Université Ninefruits »). Un signataire défini dans le modèle est prioritaire.

## Délivrance

**Délivrer une attestation** sur l’écran du dossier étudiant : type, langue, objet, exemplaires (de 1 à 20). À l’enregistrement, le PDF s’ouvre dans un nouvel onglet.

La délivrance est refusée lorsque :

- les destinataires du type ne correspondent pas (un certificat de scolarité pour un diplômé, une attestation de réussite au diplôme pour un étudiant en cours de scolarité)
- le type est inactif
- il n’existe pas de modèle pour cette langue
- un **blocage d’attestations** est en vigueur pour l’étudiant (un blocage, [chapitre 20](20-registration.md)) ; levez-le d’abord
- le dossier étudiant est hors du périmètre de consultation

La délivrance est enregistrée dans le journal d’activité (`certificate.issued` : numéro d’étudiant, type, langue, numéro de série, exemplaires, objet).

## Attestations délivrées

**Attestations > Attestations délivrées** : date de délivrance, numéro de série, type et langue, numéro d’étudiant, nom, objet, exemplaires, statut, membre du personnel. Filtrez par type et par statut. Le périmètre de consultation est le même que pour les dossiers étudiants.

- **PDF** : ouvre le fichier stocké. Il n’est pas régénéré ; il est donc exactement tel qu’il a été délivré.
- **Page de vérification** : ouvre la page publique.
- **Régénérer** : génère un nouveau PDF à partir des données actuelles, par ex. après la correction d’un modèle, et remplace le fichier. Le numéro de série et le code de vérification sont conservés ; la somme de contrôle change. Événement d’audit `certificate.rerendered`. Identique à la commande `certificate:render`.
- **Révoquer** : motif obligatoire. La ligne et le fichier sont conservés, le statut devient « révoquée » et la page de vérification répond « révoquée ». Événement d’audit `certificate.revoked`. Une attestation révoquée n’est pas rétablie ; une nouvelle est délivrée.

Les lignes du registre ne peuvent être ni modifiées ni supprimées à l’écran.

## Vérification

- L’URL de vérification (`/verify/{code}`) et un QR code sont imprimés en bas de l’attestation. Le code est une chaîne aléatoire de 20 caractères.
- La page s’ouvre sans connexion et est limitée à 30 requêtes par minute. Elle indique si l’attestation a été délivrée, son type, son numéro de série, sa date de délivrance ainsi que le nom et le numéro d’étudiant masqués. Les codes révoqués, expirés et inconnus reçoivent chacun une réponse différente.
- Pour vérifier le fichier PDF remis lui-même, comparez la somme de contrôle (SHA-256) du registre avec l’empreinte du fichier. La commande `certificate:verify {code}` le fait aussi.

## Portail étudiant

**Attestations** : les attestations délivrées sur le propre dossier de l’étudiant, avec leurs PDF. Les attestations révoquées sont listées avec leur statut, mais l’étudiant ne peut pas télécharger le PDF d’une attestation révoquée (le personnel qui l’a délivrée peut l’ouvrir pour contrôler l’enregistrement). Les fichiers des autres étudiants ne peuvent pas être ouverts (imposé dans l’application et dans la base de données).

## Questions fréquentes

- **Le nom est mal orthographié.** Corrigez la variante du nom de la personne (dans cette langue) puis cliquez sur **Régénérer**. Si l’attestation a déjà été remise, révoquez-la et délivrez-en une nouvelle.
- **J’ai modifié le modèle ; qu’en est-il des attestations déjà délivrées ?** Elles ne changent pas. Utilisez **Régénérer** si nécessaire.
- **Un numéro de série a été sauté.** Lorsqu’une erreur survient pendant la délivrance (par ex. le service PDF est indisponible), le numéro est consommé et aucune ligne de registre ne subsiste nécessairement. L’unicité des numéros est garantie, pas leur continuité.

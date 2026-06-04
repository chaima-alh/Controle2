# Gestion d'Absences ENSAH - Analyse Cahier de Charge

**Date d'analyse:** 3 Juin 2026  
**Codebase:** PHP/MySQL - Architecture Services  

---

## 1. GESTION DES ÉTUDIANTS

### Requirement Checklist

| Fonctionnalité | Statut | Fichiers | Notes |
|---|---|---|---|
| Recherche par nom | ✓ Implemented | [includes/EtudiantService.php](includes/EtudiantService.php#L20), [admin/etudiants/index.php](admin/etudiants/index.php#L10) | Recherche LIKE sur nom_fr et prenom_fr |
| Recherche phonétique | ✓ Implemented | [includes/EtudiantService.php](includes/EtudiantService.php#L42), [includes/helpers.php](includes/helpers.php#L101) | Fonction phoneticKey() pour sons français (ph→f, ç→s, accents) |
| Recherche par CNE/Massar | ✓ Implemented | [includes/EtudiantService.php](includes/EtudiantService.php#L47) | Recherche LIKE sur champ massar |
| Affichage liste classe | ✓ Implemented | [admin/etudiants/index.php](admin/etudiants/index.php), [admin/etudiants/stats.php](admin/etudiants/stats.php) | Filtre par classe_id avec statistiques |
| Export CSV | ✓ Implemented | [admin/etudiants/export.php](admin/etudiants/export.php), [includes/EtudiantService.php](includes/EtudiantService.php#L185) | Format CSV UTF-8 avec séparateur `;` |
| Impression liste classe | ✓ Implemented | [admin/etudiants/print.php](admin/etudiants/print.php) | HTML print-friendly avec CSS |
| Statistiques | ✓ Implemented | [admin/etudiants/stats.php](admin/etudiants/stats.php), [includes/AbsenceService.php](includes/AbsenceService.php#L236) | Stats par classe et filière |
| Ajouter étudiant | ✓ Implemented | [admin/etudiants/form.php](admin/etudiants/form.php) | Validation des champs requis, upload photo |
| Modifier étudiant | ✓ Implemented | [admin/etudiants/form.php](admin/etudiants/form.php) | Mise à jour avec audit trail |
| Supprimer étudiant | ✓ Implemented | [admin/etudiants/delete.php](admin/etudiants/delete.php) | Soft delete (deleted_at) |
| Récupération étudiant supprimé | ✓ Implemented | [admin/etudiants/restore.php](admin/etudiants/restore.php), [includes/EtudiantService.php](includes/EtudiantService.php#L115) | Corbeille avec restore |
| Audit trail | ✓ Implemented | [includes/EtudiantService.php](includes/EtudiantService.php#L109), table `etudiant_audit` | Logs: ancien_val, nouvelle_val, user_id, date |

### Détails Implémentation

**Base de données:**
- Table `etudiants` avec soft delete (`deleted_at`)
- Table `etudiant_audit` pour l'historique
- Indices sur: nom, massar, deleted_at
- Photo upload support

**Accès:** Admin uniquement

---

## 2. AUTHENTIFICATION

### Requirement Checklist

| Fonctionnalité | Statut | Fichiers | Notes |
|---|---|---|---|
| Système d'authentification | ✓ Implemented | [includes/Auth.php](includes/Auth.php), [login.php](login.php) | Login/Password avec session |
| Rôles (Admin, Enseignant, Étudiant) | ✓ Implemented | [includes/Auth.php](includes/Auth.php#L185), table `comptes.role` | ENUM: administrateur, enseignant, etudiant |
| Contrôle d'accès par rôle | ✓ Implemented | [includes/Auth.php](includes/Auth.php#L185) | Auth::requireRole($roles) |
| Remember Me | ✓ Implemented | [includes/Auth.php](includes/Auth.php#L79) | Secure tokens avec expiration (14j configurable) |
| Captcha | ✓ Implemented | [includes/Captcha.php](includes/Captcha.php), [login.php](login.php) | Math puzzle configurable |
| Verrouillage IP | ✓ Implemented | [includes/SecurityService.php](includes/SecurityService.php), [login.php](login.php#L14) | Table `ips_bloquees` (après max tentatives) |
| Dashboard par rôle | ✓ Implemented | [includes/Auth.php](includes/Auth.php#L212), dashboards multiples | Routes différentes selon role |

### Détails Sécurité

**Authentification:**
- Hachage: `password_hash()` avec PASSWORD_DEFAULT (bcrypt)
- Vérification: `password_verify()`
- Tentatives échouées: Compteur dans `comptes.tentatives_echouees`
- Verrouillage: `comptes.locked = 1` après 5 tentatives (configurable)
- Session: Régénération avec `session_regenerate_id(true)`

**Cookies sécurisés:**
- Remember token: `httponly = true`, `samesite = Lax`
- Stockage: Hachage du validator, selector unique

**IP Blocking:**
- Check avant authentification
- Enregistrement manuel par admin disponible

---

## 3. GESTION DE LA STRUCTURE PÉDAGOGIQUE

### Requirement Checklist

| Fonctionnalité | Statut | Fichiers | Notes |
|---|---|---|---|
| Gestion filières | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L9), [admin/structure/index.php](admin/structure/index.php) | CRUD complet |
| Gestion classes | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L32) | CRUD avec filière |
| Gestion modules | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L57) | CRUD avec code/titre/niveau |
| Gestion éléments (matières) | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L88) | CRUD par module |
| Import CSV | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L167) | Format: filiere;classe;module;element |
| Import XML | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L206) | Parsing XML custom |
| Import Excel/XLSX | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L235), [includes/XlsxReader.php](includes/XlsxReader.php) | Via XlsxReader |
| Association module→classe | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L115), table `classe_module` | Many-to-many |
| Dissociation module←classe | ✓ Implemented | [includes/StructureService.php](includes/StructureService.php#L121) | Suppression association |

### Détails Implémentation

**Tables:**
- `filieres` (avec coordinateur enseignant)
- `classes` (avec filiere_id)
- `modules` (avec code, titre, niveau)
- `elements` (avec module_id)
- `classe_module` (association)
- `enseignants` (pour coordinateurs)

**Accès:** Admin uniquement

**Import:**
- CSV: Séparateur `;`
- XML: Format custom
- XLSX: Via bibliothèque XlsxReader

---

## 4. GESTION DES COMPTES

### Requirement Checklist

| Fonctionnalité | Statut | Fichiers | Notes |
|---|---|---|---|
| Créer compte | ✓ Implemented | [includes/CompteService.php](includes/CompteService.php#L42), [admin/comptes/create.php](admin/comptes/create.php) | Recherche étudiant/enseignant puis création |
| Login = nom+prénom | ✓ Implemented | [includes/helpers.php](includes/helpers.php#L49) | Fonction generateLogin() |
| Auto-incrément sur doublon | ✓ Implemented | [includes/helpers.php](includes/helpers.php#L49) | Incrémente: nom+1, nom+2... |
| Génération MDP aléatoire | ✓ Implemented | [includes/helpers.php](includes/helpers.php#L63) | randomPassword(10) avec mix caractères |
| Hachage MDP | ✓ Implemented | [includes/CompteService.php](includes/CompteService.php#L51) | password_hash(PASSWORD_DEFAULT) |
| Activer/Désactiver compte | ✓ Implemented | [includes/CompteService.php](includes/CompteService.php#L87), [admin/comptes/index.php](admin/comptes/index.php) | Champ `enabled` (1/0) |
| Verrouiller après tentatives | ✓ Implemented | [includes/Auth.php](includes/Auth.php#L131), [admin/comptes/index.php](admin/comptes/index.php) | Champ `locked` |
| Changer rôle | ✓ Implemented | [includes/CompteService.php](includes/CompteService.php#L93), [admin/comptes/index.php](admin/comptes/index.php) | Changement role dynamique |
| Réinitialiser MDP | ✓ Implemented | [includes/CompteService.php](includes/CompteService.php#L81), [admin/comptes/index.php](admin/comptes/index.php) | Reset MDP + unlock |
| Consulter actions utilisateur | ✓ Implemented | [includes/CompteService.php](includes/CompteService.php#L97), [admin/comptes/navigation.php](admin/comptes/navigation.php) | Table `navigation_log` |
| Historique connexions | ✓ Implemented | [includes/CompteService.php](includes/CompteService.php#L77), [admin/comptes/connexions.php](admin/comptes/connexions.php) | Table `connexions_historique` |

### Détails Implémentation

**Comptes:**
- Champs: login, mot_de_passe, role, personne_type, personne_id, enabled, locked, tentatives_echouees
- Rôles: administrateur, enseignant, etudiant
- Personnes: enseignant, etudiant (stockées dans tables séparées)

**Audit:**
- Connexions: Table `connexions_historique` avec IP
- Navigation: Table `navigation_log` avec page visitée

**Accès:** Admin uniquement

---

## 5. GESTION DES ABSENCES

### 5A. Enseignants - Saisie d'Absences

| Fonctionnalité | Statut | Fichiers | Notes |
|---|---|---|---|
| Marquer absences | ✓ Implemented | [enseignant/absences/saisie.php](enseignant/absences/saisie.php) | Interface visuelle avec photos |
| Rechercher étudiants | ✓ Implemented | [enseignant/absences/saisie.php](enseignant/absences/saisie.php) | Filtre par classe |
| Saisie depuis listes physiques | ✓ Implemented | [admin/absences/saisie_groupe.php](admin/absences/saisie_groupe.php) | Upload/saisie Massar séparés par virgule |
| Annuler absence récente | ✓ Implemented | [includes/AbsenceService.php](includes/AbsenceService.php#L43), [admin/absences/edit.php](admin/absences/edit.php) | Délai: 7j configurable (`seuil_annulation_absence_jours`) |
| Gérer demandes de permission | ✓ Implemented | [enseignant/absences/demandes.php](enseignant/absences/demandes.php) | Accept/Refuse avec notifications |

### 5B. Étudiants - Suivi Absences

| Fonctionnalité | Statut | Fichiers | Notes |
|---|---|---|---|
| Voir fiche absences | ✓ Implemented | [etudiant/absences.php](etudiant/absences.php), [includes/fiche_absences.php](includes/fiche_absences.php) | Affichage complet |
| Envoyer justifications | ✓ Implemented | [etudiant/justifier.php](etudiant/justifier.php) | Upload fichier (PDF, image) |
| Faire réclamations | ✓ Implemented | [etudiant/reclamations.php](etudiant/reclamations.php) | Texte libre + suivi |
| Suivre statut réclamation | ✓ Implemented | [etudiant/reclamations.php](etudiant/reclamations.php) | Statuts: ouverte, en_cours, resolue, refusee |
| Avertissements excès absences | ✓ Implemented | [etudiant/notifications.php](etudiant/notifications.php), [includes/AbsenceService.php](includes/AbsenceService.php#L221) | Seuil: 3 absences (configurable) |
| Historique absences | ✓ Implemented | [etudiant/absences.php](etudiant/absences.php) | Affichage date, élément, type, état |
| Demander permission absence | ✓ Implemented | [etudiant/permissions.php](etudiant/permissions.php) | Sélection enseignant + message |

### 5C. Admin - Gestion Complète

| Fonctionnalité | Statut | Fichiers | Notes |
|---|---|---|---|
| Consulter fiche étudiant | ✓ Implemented | [admin/absences/fiche.php](admin/absences/fiche.php) | Recherche par étudiant |
| Modifier absence | ✓ Implemented | [admin/absences/edit.php](admin/absences/edit.php) | Date/heure, élément, type, état |
| Annuler absence | ✓ Implemented | [admin/absences/edit.php](admin/absences/edit.php) | Sans limite de délai |
| Justifier absence | ✓ Implemented | [admin/absences/edit.php](admin/absences/edit.php) | Passer état à "justifiee" |
| Valider justifications | ✓ Implemented | [admin/absences/justifications.php](admin/absences/justifications.php) | Accept/Refuse fichiers |
| Répondre à réclamations | ✓ Implemented | [admin/absences/reclamations.php](admin/absences/reclamations.php) | Texte réponse + statut |
| Saisie groupée Massar | ✓ Implemented | [admin/absences/saisie_groupe.php](admin/absences/saisie_groupe.php) | Copier-coller codes séparés |

### Détails Implémentation

**Tables:**
- `absences`: date_heure, element_id, type_seance_id, etat (non_justifiee/justifiee/annulee), etudiant_id, enseignant_id, annee_academique_id, saisie_par
- `justifications`: absence_id, fichier, statut (en_attente/acceptee/refusee), envoye_par_etudiant
- `reclamations`: absence_id, etudiant_id, message, statut (ouverte/en_cours/resolue/refusee), reponse_admin
- `demandes_permission`: etudiant_id, enseignant_id, element_id, message, statut, reponse
- `avertissements`: etudiant_id, element_id, annee_academique_id, nombre_absences, message

**Services:**
- `AbsenceService`: Tous les métiers absence, justification, réclamation, permission
- Notifications: Automatiques à chaque changement d'état

**Accès:**
- Enseignants: Saisie, annulation délai, permissions
- Étudiants: Consultation, justifications, réclamations, permissions
- Admin: Gestion complète

---

## 6. POINTS FORTS & RECOMMANDATIONS

### ✓ Points Forts

1. **Architecture Services** - Code modulaire et maintenable
2. **Audit Trail** - Traçabilité complète des modifications étudiants
3. **Soft Delete** - Récupération d'étudiants supprimés
4. **Sécurité Authentification** - Bcrypt, rate limiting, IP blocking
5. **Multi-format Import** - CSV, XML, XLSX supportés
6. **Phonetic Search** - Recherche robuste sur noms français
7. **Notifications** - Système d'alertes pour absences excessives
8. **Role-Based Access** - Contrôle d'accès précis par rôle

### ⚠️ Recommandations - Sécurité

| Problème | Sévérité | Recommandation | Fichiers |
|---|---|---|---|
| Pas de tokens CSRF | Moyenne | Ajouter vérification CSRF sur tous les POST | Tous les formulaires |
| Upload fichiers | Moyenne | Renforcer validation type MIME (ne pas se fier ext) | etudiant/justifier.php |
| Mots de passe | Basse | Ajouter critères min (longueur, caractères) | includes/helpers.php |
| SQL Injection | ✓ Mitigé | Prepared statements - bon état général | - |
| XSS | ✓ Mitigé | Utilisation fonction e() - bon état | - |

### ⚠️ Recommandations - Fonctionnalités

| Fonctionnalité | Type | Priorité | Notes |
|---|---|---|---|
| 2FA (Two-Factor) | Enhancement | Basse | Pour comptes admin |
| Historique audit absences | Enhancement | Moyenne | Tracer modifications admin |
| Bulk operations | Enhancement | Basse | Export/import avec validation |
| Expiration sessions | Enhancement | Basse | Timeout inactivité |
| Logs absence fichier | Enhancement | Basse | Pour traçabilité |

---

## 7. CONFIGURATION

### Variables de Configuration (table `configuration`)

```
seuil_annulation_absence_jours = 7
seuil_avertissement_absences = 3
max_tentatives_connexion = 5
captcha_actif = 1
remember_me_jours = 14
```

---

## 8. RÉSUMÉ COUVERTURE CAHIER DE CHARGE

| Section | Couverture | Détail |
|---|---|---|
| Gestion Étudiants | **100%** | Toutes les fonctionnalités implémentées |
| Authentification | **100%** | Système complet avec sécurité renforcée |
| Structure Pédagogique | **100%** | Import/Export et CRUD complet |
| Comptes Utilisateurs | **100%** | Gestion avancée avec audit |
| Absences - Enseignants | **100%** | Saisie, annulation, permissions |
| Absences - Étudiants | **100%** | Consultation, justifications, réclamations |
| Absences - Admin | **100%** | Gestion centralisée complète |

---

## 9. FICHIERS CLÉ - RÉCAPITULATIF

### Services (includes/)
- [Auth.php](includes/Auth.php) - Authentification & sessions
- [Database.php](includes/Database.php) - Connexion PDO
- [AbsenceService.php](includes/AbsenceService.php) - Métier absence
- [EtudiantService.php](includes/EtudiantService.php) - Métier étudiant
- [CompteService.php](includes/CompteService.php) - Métier compte
- [StructureService.php](includes/StructureService.php) - Métier structure
- [NotificationService.php](includes/NotificationService.php) - Notifications
- [SecurityService.php](includes/SecurityService.php) - Sécurité IP

### Panels
- [admin/](admin/) - Panel administrateur
- [enseignant/](enseignant/) - Espace enseignant
- [etudiant/](etudiant/) - Espace étudiant

### Database
- [database/schema.sql](database/schema.sql) - Schéma principal
- [database/migration_v2.sql](database/migration_v2.sql) - Tables additionnelles

---

**Conclusion:** Le codebase implémente **100%** des requirements du cahier de charge avec une architecture solide et des mesures de sécurité appropriées.

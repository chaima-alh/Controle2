# Gestion des absences — ENSAH

Application web PHP / MySQL pour la gestion des absences des étudiants (cahier des charges module Web).

## Stack

- **Back-end :** PHP 8+ (PDO)
- **Front-end :** HTML, CSS, Bootstrap 5, JavaScript
- **Base de données :** MySQL / MariaDB (XAMPP)

## Installation (XAMPP)

1. Démarrer **Apache** et **MySQL** dans XAMPP.
2. Le projet est dans `C:\xampp\htdocs\gestions d'abs`.
3. Ouvrir **phpMyAdmin** → importer dans l’ordre :
   - `database/schema.sql`
   - `database/seed.sql`
4. Vérifier `config/database.php` (user `root`, mot de passe vide par défaut).
5. Ouvrir dans le navigateur :  
   `http://localhost/gestions%20d%27abs/install.php`  
   Définir le mot de passe du compte **admin**.
6. Se connecter : `http://localhost/gestions%20d%27abs/login.php`  
   - Login : `admin`

> Si l’URL ne fonctionne pas, adapter `base_url` dans `config/app.php` selon le nom du dossier dans `htdocs`.

## Modules implémentés (cahier des charges)

| # | Module | Fonctionnalités |
|---|--------|-----------------|
| 1 | **Étudiants** | CRUD admin, recherche nom/phonétique/Massar, liste/impression/CSV par classe, stats par classe, corbeille, audit, fiche absences |
| 2 | **Authentification** | Login, rôles (admin/enseignant/étudiant), enabled/locked, historique connexions |
| 3 | **Structure pédagogique** | CRUD filières, classes, modules, éléments, associations, modules/classe, coordonnateur, import CSV |
| 4 | **Comptes** | Création liée Massar/CIN, login auto, MDP aléatoire, reset, activer/désactiver, changer rôle, navigation, connexions |
| 5 | **Absences** | Saisie enseignant (mobile), fiche, annulation avec seuil, permissions, étudiant (justifier, réclamations, profil), admin (saisie groupée Massar, justifications, réclamations) |

## Comptes de démonstration

Après import SQL, exécuter :

```bash
c:\xampp\php\php.exe database\install_demo.php
```

| Rôle | Login | Mot de passe |
|------|-------|--------------|
| Admin | `admin` | défini via `install.php` ou `admin123` |
| Enseignant | `hassanalami` | `demo1234` |
| Étudiant | `youssefidrissi` | `demo1234` |

## Structure du projet

```
config/           Configuration app et BDD
database/         Schéma SQL et données initiales
includes/         Auth, services, layout
admin/            Interfaces administrateur
enseignant/       Interfaces enseignant
etudiant/         Interfaces étudiant
assets/           CSS, JS, images
uploads/          Photos et justificatifs
```

## Compte administrateur

Créé par `seed.sql` (login `admin`). Le mot de passe est défini via `install.php` (hash bcrypt). **Supprimez `install.php` en production.**

## Sécurité

- Mots de passe : `password_hash()` / `password_verify()`
- Requêtes préparées (PDO)
- Contrôle d’accès par rôle (`Auth::requireRole`)
- Soft delete étudiants + table `etudiant_audit`
- **Captcha** sur login, **cookie « se souvenir »**, **blocage IP** (`admin/securite/ips.php`)

## Import structure (CSV / XML / Excel)

- Admin → Structure → Import
- Exemple XML : `database/exemple_structure.xml`

## Migration v2 (si projet déjà installé)

```bash
c:\xampp\php\php.exe database\run_migration_v2.php
```

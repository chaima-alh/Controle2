# Gestion des absences — ENSAH

Application web PHP / MySQL pour la gestion des absences des étudiants (projet de cas d'étude).

## Aperçu

- Langage : PHP 8+ (PDO)
- Front : HTML, CSS, Bootstrap 5, JavaScript
- BDD : MySQL / MariaDB (XAMPP)

Repo : https://github.com/chaima-alh/Controle2

## Installation rapide (XAMPP)

1. Démarrez **Apache** et **MySQL** via XAMPP.
2. Placez le projet dans `C:\xampp\htdocs\gestions d'abs`.
3. Créez la base et importez : `database/schema.sql` puis `database/seed.sql`.
4. Vérifiez `config/database.php` (utilisateur/MDP).
5. (Optionnel) Exécutez le script demo :

```powershell
c:\xampp\php\php.exe database\install_demo.php
```

6. Ouvrez `http://localhost/gestions%20d%27abs/login.php` et connectez-vous.

Note : adaptez `base_url` dans `config/app.php` si nécessaire.

## Comptes de démonstration

| Rôle | Login | Mot de passe |
|------|-------|--------------|
| Admin | `admin` | défini via `install.php` ou `admin123` |
| Enseignant | `hassanalami` | `demo1234` |
| Étudiant | `youssefidrissi` | `demo1234` |

## Structure du projet

```
config/           Configuration app et BDD
database/         Schéma SQL, migrations et données d'exemple
includes/         Classes d'accès, services et layout
admin/            Interface administrateur
enseignant/       Interface enseignant
etudiant/         Interface étudiant
assets/           CSS, JS, images
uploads/          Fichiers utilisateurs (exclu du dépôt)
```

## Sécurité & bonnes pratiques

- Mots de passe : `password_hash()` / `password_verify()`
- Requêtes préparées (PDO) pour éviter les injections SQL
- Contrôles d'accès par rôle (`Auth`)
- Supprimez `install.php` après configuration en production

## Contribuer

1. Forkez le repo et créez une branche : `feature/xxx`.
2. Faites vos modifications, puis :

```bash
git add .
git commit -m "Add feature/fix"
git push origin feature/xxx
```

3. Ouvrez une Pull Request sur GitHub.

## Licence

Ce projet n'a pas de licence spécifiée — ajoutez-en une (ex. MIT) si vous souhaitez autoriser la réutilisation.

---

Pour toute aide supplémentaire (CI, licence, README en anglais), dites-moi ce que vous voulez ajouter.

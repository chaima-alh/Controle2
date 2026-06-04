<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['enseignant']);
logNavigation($pdo, Auth::user()['id'], '/enseignant/dashboard.php');
$pageTitle = 'Espace enseignant';
require __DIR__ . '/../includes/layout/header.php';
?>
<h1 class="h3 mb-4">Espace enseignant</h1>
<div class="d-grid gap-2 col-md-6">
<a href="<?= e($appConfig['base_url']) ?>/enseignant/absences/saisie.php" class="btn btn-danger btn-lg">Saisir des absences (mobile)</a>
<a href="<?= e($appConfig['base_url']) ?>/enseignant/absences/fiche.php" class="btn btn-outline-primary">Fiche d'absence d'un étudiant</a>
<a href="<?= e($appConfig['base_url']) ?>/enseignant/absences/demandes.php" class="btn btn-outline-secondary">Demandes de permission</a>
<a href="<?= e($appConfig['base_url']) ?>/enseignant/etudiants/index.php" class="btn btn-outline-secondary">Rechercher un étudiant</a>
</div>
<?php require __DIR__ . '/../includes/layout/footer.php';

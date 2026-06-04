<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
logNavigation($pdo, Auth::user()['id'], '/admin/dashboard.php');
$stats = [
    'etudiants' => (int) $pdo->query('SELECT COUNT(*) FROM etudiants WHERE deleted_at IS NULL')->fetchColumn(),
    'classes'   => (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn(),
    'comptes'   => (int) $pdo->query('SELECT COUNT(*) FROM comptes')->fetchColumn(),
    'absences'  => (int) $pdo->query('SELECT COUNT(*) FROM absences WHERE etat!="annulee"')->fetchColumn(),
];
$pageTitle = 'Administration';
require __DIR__ . '/../includes/layout/header.php';
?>
<h1 class="h3 mb-4">Tableau de bord administrateur</h1>
<div class="row g-3 mb-4">
<?php foreach (['etudiants'=>'Étudiants','classes'=>'Classes','comptes'=>'Comptes','absences'=>'Absences'] as $k=>$l): ?>
<div class="col-6 col-md-3"><div class="card"><div class="card-body text-center"><div class="text-muted small"><?= $l ?></div><div class="display-6"><?= $stats[$k] ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="row g-2">
<div class="col-md-4"><a href="<?= e($appConfig['base_url']) ?>/admin/etudiants/index.php" class="btn btn-outline-primary w-100">Module 1 — Étudiants</a></div>
<div class="col-md-4"><a href="<?= e($appConfig['base_url']) ?>/admin/structure/index.php" class="btn btn-outline-primary w-100">Module 3 — Structure pédagogique</a></div>
<div class="col-md-4"><a href="<?= e($appConfig['base_url']) ?>/admin/comptes/index.php" class="btn btn-outline-primary w-100">Module 4 — Comptes</a></div>
<div class="col-md-4"><a href="<?= e($appConfig['base_url']) ?>/admin/absences/index.php" class="btn btn-outline-primary w-100">Module 5 — Absences</a></div>
</div>
<?php require __DIR__ . '/../includes/layout/footer.php';

<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$stats = (new AbsenceService($pdo))->statsParClasse();
$pageTitle = 'Statistiques par classe';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Statistiques d'étudiants par classe</h1>
<table class="table table-striped bg-white">
<thead><tr><th>Filière</th><th>Classe</th><th>Nombre d'étudiants</th></tr></thead>
<tbody>
<?php foreach ($stats as $s): ?>
<tr><td><?= e($s['filiere']) ?></td><td><?= e($s['classe_nom']) ?></td><td><?= (int)$s['nb_etudiants'] ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/../../includes/layout/footer.php';

<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$svc = new CompteService($pdo);
$compteId = (int)($_GET['compte_id'] ?? 0);
$rows = $svc->navigationByCompte($compteId);
$pageTitle = 'Pages visitées';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Pages visitées (compte #<?= $compteId ?>)</h1>
<table class="table table-sm bg-white"><thead><tr><th>Date</th><th>Page</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['visite_le']) ?></td><td><?= e($r['page']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<a href="index.php" class="btn btn-link">Retour</a>
<?php require __DIR__ . '/../../includes/layout/footer.php';

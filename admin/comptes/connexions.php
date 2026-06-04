<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$svc = new CompteService($pdo);
$rows = $svc->connexionsHistorique();
$pageTitle = 'Historique connexions';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Historique des connexions</h1>
<table class="table table-sm table-striped bg-white">
<thead><tr><th>Date/heure</th><th>Login</th><th>Rôle</th><th>IP</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['connecte_le']) ?></td><td><?= e($r['login']) ?></td><td><?= e($r['role']) ?></td><td><?= e($r['adresse_ip']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/../../includes/layout/footer.php';

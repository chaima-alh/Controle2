<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$svc = new CompteService($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $svc->saveEnseignant($_POST, (int)($_POST['id'] ?? 0) ?: null);
        flash('success', 'Enseignant enregistré.');
    }
    redirect('/admin/enseignants/index.php');
}
$rows = (new StructureService($pdo))->listEnseignants();
$pageTitle = 'Enseignants';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Enseignants</h1>
<form method="post" class="card card-body mb-3 row g-2">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="col-md-3"><input name="nom_fr" class="form-control" placeholder="Nom FR" required></div>
<div class="col-md-3"><input name="prenom_fr" class="form-control" placeholder="Prénom FR" required></div>
<div class="col-md-2"><input name="cin" class="form-control" placeholder="CIN" required></div>
<div class="col-md-2"><input name="email" class="form-control" placeholder="Email"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Ajouter</button></div>
</form>
<table class="table bg-white"><thead><tr><th>Nom</th><th>CIN</th><th>Email</th><th>Tél.</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['nom_fr'].' '.$r['prenom_fr']) ?></td><td><?= e($r['cin']) ?></td><td><?= e($r['email']??'') ?></td><td><?= e($r['telephone']??'') ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/../../includes/layout/footer.php';

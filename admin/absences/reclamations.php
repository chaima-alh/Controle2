<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$absSvc = new AbsenceService($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $absSvc->repondreReclamation((int)$_POST['id'], $_POST['reponse'], $_POST['statut']);
        flash('success', 'Réponse envoyée.');
    }
    redirect('/admin/absences/reclamations.php');
}
$rows = $absSvc->listReclamationsOuvertes();
$pageTitle = 'Réclamations';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Réclamations</h1>
<?php foreach ($rows as $r): ?>
<div class="card mb-3"><div class="card-body">
<strong><?= e($r['nom_fr'].' '.$r['prenom_fr']) ?></strong> — <?= e($r['element_titre']) ?> (<?= e($r['date_heure']) ?>)
<p><?= e($r['message']) ?></p>
<form method="post" class="row g-2">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<div class="col-md-8"><textarea name="reponse" class="form-control" placeholder="Réponse" required></textarea></div>
<div class="col-md-2"><select name="statut" class="form-select"><option value="resolue">Résolue</option><option value="refusee">Refusée</option></select></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Répondre</button></div>
</form></div></div>
<?php endforeach; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

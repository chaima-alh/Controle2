<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['etudiant']);
$eid = personneIdFromUser(Auth::user());
$absSvc = new AbsenceService($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $absSvc->creerReclamation((int)$_POST['absence_id'], $eid, trim($_POST['message']));
        flash('success', 'Réclamation envoyée.');
    }
    redirect('/etudiant/reclamations.php');
}
$absences = $absSvc->ficheEtudiant($eid);
$reclamations = $absSvc->reclamationsEtudiant($eid);
$pageTitle = 'Réclamations';
require __DIR__ . '/../includes/layout/header.php';
?>
<h1 class="h4 mb-3">Réclamations</h1>
<form method="post" class="card card-body mb-3">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<select name="absence_id" class="form-select mb-2" required>
<?php foreach ($absences as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['element_titre'].' — '.date('d/m/Y', strtotime($a['date_heure']))) ?></option><?php endforeach; ?>
</select>
<textarea name="message" class="form-control mb-2" placeholder="Message à l'administrateur" required></textarea>
<button class="btn btn-primary">Envoyer réclamation</button>
</form>
<h2 class="h6">Suivi</h2>
<?php foreach ($reclamations as $r): ?>
<div class="card mb-2"><div class="card-body small">
<strong><?= e($r['statut']) ?></strong> — <?= e($r['element_titre']) ?><br>
<?= e($r['message']) ?>
<?php if ($r['reponse_admin']): ?><hr><em>Réponse :</em> <?= e($r['reponse_admin']) ?><?php endif; ?>
</div></div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/layout/footer.php';

<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['etudiant']);
$eid = personneIdFromUser(Auth::user());
$absSvc = new AbsenceService($pdo);
$struct = new StructureService($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $absSvc->creerDemandePermission($eid, (int)$_POST['enseignant_id'], (int)($_POST['element_id'] ?? 0) ?: null, trim($_POST['message']));
        flash('success', 'Demande envoyée.');
    }
    redirect('/etudiant/permissions.php');
}
$demandes = $absSvc->demandesEtudiant($eid);
$enseignants = $struct->listEnseignants();
$elements = $struct->listElements();
$pageTitle = 'Permissions';
require __DIR__ . '/../includes/layout/header.php';
?>
<h1 class="h4 mb-3">Demande de permission d'absence</h1>
<form method="post" class="card card-body mb-3">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<select name="enseignant_id" class="form-select mb-2" required><?php foreach ($enseignants as $en): ?><option value="<?= (int)$en['id'] ?>"><?= e($en['nom_fr'].' '.$en['prenom_fr']) ?></option><?php endforeach; ?></select>
<select name="element_id" class="form-select mb-2"><option value="">Matière (optionnel)</option><?php foreach ($elements as $el): ?><option value="<?= (int)$el['id'] ?>"><?= e($el['titre']) ?></option><?php endforeach; ?></select>
<textarea name="message" class="form-control mb-2" required placeholder="Message"></textarea>
<button class="btn btn-primary">Envoyer</button>
</form>
<h2 class="h6">Mes demandes</h2>
<?php foreach ($demandes as $d): ?>
<div class="card mb-1"><div class="card-body small"><?= e($d['ens_nom'].' '.$d['ens_prenom']) ?> — <span class="badge bg-secondary"><?= e($d['statut']) ?></span><br><?= e($d['message']) ?></div></div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/layout/footer.php';

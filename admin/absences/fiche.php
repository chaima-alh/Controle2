<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$absSvc = new AbsenceService($pdo);
$etuSvc = new EtudiantService($pdo);
$etudiantId = (int)($_GET['etudiant_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['identifiant'])) {
    $st = $pdo->prepare('SELECT id FROM etudiants WHERE identifiant=? AND deleted_at IS NULL');
    $st->execute([trim($_POST['identifiant'])]);
    $etudiantId = (int)$st->fetchColumn();
    if (!$etudiantId) {
        flash('error', 'Identifiant inconnu.');
    }
}

if (!empty($_GET['annuler'])) {
    $absSvc->annuler((int)$_GET['annuler'], true);
    flash('success', 'Absence annulée.');
    redirect('/admin/absences/fiche.php?etudiant_id='.$etudiantId);
}

$pageTitle = 'Fiche absences';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Fiche d'absences</h1>
<form method="post" class="row g-2 mb-3">
<div class="col-md-4"><input name="identifiant" class="form-control" placeholder="Identifiant étudiant" required></div>
<div class="col-md-2"><button class="btn btn-primary">Chercher</button></div>
</form>
<?php if ($etudiantId): $canEdit=true; $adminMode=true; require __DIR__ . '/../../includes/fiche_absences.php'; endif; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

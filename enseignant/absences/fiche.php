<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['enseignant']);
$user = Auth::user();
logNavigation($pdo, $user['id'], '/enseignant/absences/fiche.php');
$absSvc = new AbsenceService($pdo);
$etudiantId = (int)($_GET['etudiant_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $st = $pdo->prepare('SELECT id FROM etudiants WHERE identifiant=? AND deleted_at IS NULL');
        $st->execute([trim($_POST['identifiant'] ?? '')]);
        $etudiantId = (int)$st->fetchColumn();
        if (!$etudiantId) {
            flash('error', 'Aucun étudiant avec cet identifiant.');
        }
    }
}

if (!empty($_GET['annuler'])) {
    if ($absSvc->annuler((int)$_GET['annuler'], false)) {
        flash('success', 'Absence annulée.');
    } else {
        flash('error', 'Annulation impossible (délai dépassé ou déjà annulée).');
    }
    redirect('/enseignant/absences/fiche.php?etudiant_id='.$etudiantId);
}

$pageTitle = 'Fiche absence étudiant';
require __DIR__ . '/../../includes/layout/header.php';
if ($e = flash('error')): ?><div class="alert alert-danger"><?= e($e) ?></div><?php endif; ?>
<form method="post" class="mb-3 row g-2">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="col-md-6"><input name="identifiant" class="form-control form-control-lg" placeholder="Identifiant étudiant" required></div>
<div class="col-md-3"><button class="btn btn-primary btn-lg w-100">Rechercher</button></div>
</form>
<?php if ($etudiantId):
    $canEdit = true;
    require __DIR__ . '/../../includes/fiche_absences.php';
endif; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

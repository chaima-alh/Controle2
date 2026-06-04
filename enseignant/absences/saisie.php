<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['enseignant']);
$user = Auth::user();
$ensId = personneIdFromUser($user);
if (!$ensId) { exit('Compte non lié à un enseignant.'); }
logNavigation($pdo, $user['id'], '/enseignant/absences/saisie.php');
$absSvc = new AbsenceService($pdo);
$struct = new StructureService($pdo);
$etuSvc = new EtudiantService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_absences'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $ids = array_map('intval', $_POST['etudiants'] ?? []);
        if ($ids) {
            $absSvc->enregistrerAbsences(
                $ids,
                (int)$_POST['element_id'],
                (int)$_POST['type_seance_id'],
                $ensId,
                $_POST['date_heure'] ?? date('Y-m-d H:i:s')
            );
            flash('success', count($ids).' absence(s) enregistrée(s).');
        }
    }
    redirect('/enseignant/absences/saisie.php?classe_id='.(int)$_POST['classe_id']);
}

$classeId = (int)($_GET['classe_id'] ?? $_POST['classe_id'] ?? 0);
$etudiants = $classeId ? $etuSvc->search(['classe_id' => $classeId]) : [];
$classes = $etuSvc->listClasses();
$elements = $struct->listElements();
$types = $absSvc->typesSeance();
$pageTitle = 'Saisie absences';
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="saisie-absences">
<h1 class="h4 mb-3">Saisie des absences</h1>
<?php if ($m = flash('success')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>

<form method="get" class="card card-body mb-3">
<label class="form-label">1. Choisir la classe</label>
<select name="classe_id" class="form-select form-select-lg" onchange="this.form.submit()">
<option value="">—</option>
<?php foreach ($classes as $c): ?>
<option value="<?= (int)$c['id'] ?>" <?= $classeId===(int)$c['id']?'selected':'' ?>><?= e($c['filiere_alias'].' — '.$c['nom']) ?></option>
<?php endforeach; ?>
</select>
</form>

<?php if ($classeId): ?>
<form method="post">
<input type="hidden" name="classe_id" value="<?= $classeId ?>">
<div class="card card-body mb-3">
<label class="form-label">2. Informations séance</label>
<div class="row g-2">
<div class="col-12 col-md-4"><input type="datetime-local" name="date_heure" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required></div>
<div class="col-12 col-md-4"><select name="element_id" class="form-select" required>
<?php foreach ($elements as $el): ?><option value="<?= (int)$el['id'] ?>"><?= e($el['titre']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-12 col-md-4"><select name="type_seance_id" class="form-select" required>
<?php foreach ($types as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['libelle']) ?></option><?php endforeach; ?>
</select></div>
</div>
</div>

<p class="text-muted small">3. Cliquez sur les étudiants absents puis validez</p>
<div class="row g-2 mb-3">
<?php foreach ($etudiants as $e): ?>
<div class="col-6 col-md-4 col-lg-3">
<label class="card student-card h-100 text-center p-2 mb-0">
<input type="checkbox" name="etudiants[]" value="<?= (int)$e['id'] ?>" class="d-none student-check">
<img src="<?= photoUrl($appConfig, $e['photo']) ?>" class="student-photo-lg mx-auto mb-1" alt="">
<div class="small fw-bold"><?= e($e['nom_fr']) ?></div>
<div class="small"><?= e($e['prenom_fr']) ?></div>
</label>
</div>
<?php endforeach; ?>
</div>
<button type="submit" name="valider_absences" value="1" class="btn btn-danger btn-lg w-100 sticky-bottom">Valider les absences sélectionnées</button>
</form>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php';

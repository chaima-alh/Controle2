<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$absSvc = new AbsenceService($pdo);
$struct = new StructureService($pdo);
$step = (int)($_POST['step'] ?? 1);
$preview = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } elseif ($step === 1) {
        $preview = $absSvc->findByMassarList($_POST['massar_list'] ?? '');
        $_SESSION['saisie_groupe'] = array_merge($_POST, ['found_ids' => array_column($preview['found'], 'id')]);
        $step = 2;
    } elseif ($step === 2 && isset($_POST['confirm'])) {
        $data = $_SESSION['saisie_groupe'] ?? [];
        $ensId = (int)($data['enseignant_id'] ?? 0);
        if ($ensId && !empty($data['found_ids'])) {
            $absSvc->enregistrerAbsences(
                $data['found_ids'],
                (int)$data['element_id'],
                (int)$data['type_seance_id'],
                $ensId,
                $data['date_heure'],
                'administrateur'
            );
            flash('success', count($data['found_ids']).' absence(s) enregistrée(s).');
            unset($_SESSION['saisie_groupe']);
            redirect('/admin/absences/index.php');
        }
    }
}
$data = $_SESSION['saisie_groupe'] ?? null;
if ($step === 2 && $data) {
    $preview = ['found' => []];
    foreach ($data['found_ids'] as $id) {
        $st = $pdo->prepare('SELECT * FROM etudiants WHERE id=?');
        $st->execute([$id]);
        if ($e = $st->fetch()) $preview['found'][] = $e;
    }
}
$elements = $struct->listElements();
$types = $absSvc->typesSeance();
$enseignants = $struct->listEnseignants();
$pageTitle = 'Saisie groupée';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Saisie absences (fiche papier)</h1>
<?php if ($step === 1): ?>
<form method="post" class="card card-body">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="step" value="1">
<div class="row g-2 mb-2">
<div class="col-md-4"><label class="form-label">Date/heure séance</label><input type="datetime-local" name="date_heure" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Élément</label><select name="element_id" class="form-select" required><?php foreach ($elements as $el): ?><option value="<?= (int)$el['id'] ?>"><?= e($el['titre']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Type séance</label><select name="type_seance_id" class="form-select" required><?php foreach ($types as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['libelle']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Enseignant</label><select name="enseignant_id" class="form-select" required><?php foreach ($enseignants as $en): ?><option value="<?= (int)$en['id'] ?>"><?= e($en['nom_fr'].' '.$en['prenom_fr']) ?></option><?php endforeach; ?></select></div>
</div>
<label class="form-label">Codes Massar absents (séparés par virgule)</label>
<textarea name="massar_list" class="form-control" rows="3" required placeholder="M123456,M654321"></textarea>
<button class="btn btn-primary mt-2">Vérifier la liste</button>
</form>
<?php else: ?>
<div class="alert alert-info">Vérifiez les étudiants avant validation définitive.</div>
<?php if (!empty($preview['not_found'] ?? [])): ?><div class="alert alert-warning">Non trouvés : <?= e(implode(', ', $preview['not_found'])) ?></div><?php endif; ?>
<div class="row g-2 mb-3">
<?php foreach ($preview['found'] as $e): ?>
<div class="col-6 col-md-3 text-center">
<img src="<?= photoUrl($appConfig, $e['photo']) ?>" class="student-photo d-block mx-auto mb-1">
<small><?= e($e['nom_fr'].' '.$e['prenom_fr']) ?><br><?= e($e['massar']) ?></small>
</div>
<?php endforeach; ?>
</div>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="step" value="2"><input type="hidden" name="confirm" value="1">
<button class="btn btn-success">Valider la saisie</button>
<a href="saisie_groupe.php" class="btn btn-link">Annuler</a></form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

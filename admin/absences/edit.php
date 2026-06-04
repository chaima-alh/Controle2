<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$absSvc = new AbsenceService($pdo);
$struct = new StructureService($pdo);
$user = Auth::user();
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM absences WHERE id=?');
$st->execute([$id]);
$abs = $st->fetch();
if (!$abs) { redirect('/admin/absences/index.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
        redirect('/admin/absences/index.php');
    }
    
    $absSvc->updateAbsence($id, $_POST, (int)$user['id']);
    if ($_POST['etat'] === 'justifiee') { $absSvc->marquerJustifiee($id); }
    if (!empty($_FILES['justif']['name'])) {
        $validation = validateUploadedFile($_FILES['justif'], [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif'
        ]);
        
        if (!$validation['valid']) {
            flash('error', implode(' ', $validation['errors']));
        } else {
            $f = 'just_'.uniqid().'_'.basename($_FILES['justif']['name']);
            move_uploaded_file($_FILES['justif']['tmp_name'], $appConfig['upload_path'].'/justifications/'.$f);
            $absSvc->ajouterJustification($id, $f, false);
            flash('success', 'Justification ajoutée et absence mise à jour.');
        }
    } else {
        flash('success', 'Absence mise à jour.');
    }
    redirect('/admin/absences/fiche.php?etudiant_id='.(int)($_GET['etudiant_id']??$abs['etudiant_id']));
}
$pageTitle = 'Modifier absence';
require __DIR__ . '/../../includes/layout/header.php';
?>
<form method="post" enctype="multipart/form-data" class="card card-body">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<label class="form-label">Date et heure</label>
<input type="datetime-local" name="date_heure" class="form-control mb-2" value="<?= e(date('Y-m-d\TH:i', strtotime($abs['date_heure']))) ?>" required>
<label class="form-label">Élément</label>
<select name="element_id" class="form-select mb-2" required><?php foreach ($struct->listElements() as $el): ?><option value="<?= (int)$el['id'] ?>" <?= (int)$abs['element_id']===(int)$el['id']?'selected':'' ?>><?= e($el['titre']) ?></option><?php endforeach; ?></select>
<label class="form-label">Type de séance</label>
<select name="type_seance_id" class="form-select mb-2" required><?php foreach ($absSvc->typesSeance() as $t): ?><option value="<?= (int)$t['id'] ?>" <?= (int)$abs['type_seance_id']===(int)$t['id']?'selected':'' ?>><?= e($t['libelle']) ?></option><?php endforeach; ?></select>
<label class="form-label">État</label>
<select name="etat" class="form-select mb-2" required>
<option value="non_justifiee" <?= $abs['etat']==='non_justifiee'?'selected':'' ?>>Non justifiée</option>
<option value="justifiee" <?= $abs['etat']==='justifiee'?'selected':'' ?>>Justifiée</option>
<option value="annulee" <?= $abs['etat']==='annulee'?'selected':'' ?>>Annulée</option>
</select>
<label class="form-label">Joindre justification (scan)</label>
<input type="file" name="justif" class="form-control mb-2" accept=".pdf,.jpg,.jpeg,.png,.gif">
<small class="text-muted d-block mb-2">Formats acceptés: PDF, JPEG, PNG, GIF. Taille max: 5MB.</small>
<button class="btn btn-primary">Enregistrer</button>
</form>
<?php require __DIR__ . '/../../includes/layout/footer.php';

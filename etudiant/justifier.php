<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['etudiant']);
$eid = personneIdFromUser(Auth::user());
$absSvc = new AbsenceService($pdo);
$absences = $absSvc->ficheEtudiant($eid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
        redirect('/etudiant/justifier.php');
    }
    
    $aid = (int)$_POST['absence_id'];
    if (!empty($_FILES['fichier']['name'])) {
        $validation = validateUploadedFile($_FILES['fichier'], [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif'
        ]);
        
        if (!$validation['valid']) {
            flash('error', implode(' ', $validation['errors']));
            redirect('/etudiant/justifier.php');
        }
        
        $f = 'just_'.uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['fichier']['name']);
        move_uploaded_file($_FILES['fichier']['tmp_name'], $appConfig['upload_path'].'/justifications/'.$f);
        $absSvc->ajouterJustification($aid, $f, true);
        flash('success', 'Justification envoyée (en attente de validation).');
    }
    redirect('/etudiant/justifier.php');
}

$pageTitle = 'Justifier une absence';
require __DIR__ . '/../includes/layout/header.php';
?>
<h1 class="h4 mb-3">Envoyer une justification</h1>
<form method="post" enctype="multipart/form-data" class="card card-body">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<label class="form-label">Absence concernée</label>
<select name="absence_id" class="form-select mb-2" required>
<?php foreach ($absences as $a): if ($a['etat']==='annulee') continue; ?>
<option value="<?= (int)$a['id'] ?>"><?= e(date('d/m/Y', strtotime($a['date_heure'])).' — '.$a['element_titre'].' ('.$a['etat'].')') ?></option>
<?php endforeach; ?>
</select>
<label class="form-label">Fichier (PDF, image…)</label>
<input type="file" name="fichier" class="form-control mb-2" required accept=".pdf,.jpg,.jpeg,.png,.gif">
<small class="text-muted d-block mb-2">Formats acceptés: PDF, JPEG, PNG, GIF. Taille max: 5MB.</small>
<button class="btn btn-primary">Envoyer</button>
</form>
<?php require __DIR__ . '/../includes/layout/footer.php';

<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['etudiant']);
$eid = personneIdFromUser(Auth::user());
$etuSvc = new EtudiantService($pdo);
$etudiant = $etuSvc->findActive($eid);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
        redirect('/etudiant/profil.php');
    }
    $data = [
        'nom_fr'=>$etudiant['nom_fr'], 'prenom_fr'=>$etudiant['prenom_fr'],
        'nom_ar'=>$etudiant['nom_ar'], 'prenom_ar'=>$etudiant['prenom_ar'],
        'identifiant'=>$etudiant['identifiant'], 'cin'=>$etudiant['cin'], 'massar'=>$etudiant['massar'],
        'email'=>trim($_POST['email']??''), 'telephone'=>trim($_POST['telephone']??''),
        'niveau_actuel'=>$etudiant['niveau_actuel'], 'cursus'=>$etudiant['cursus'],
        'date_naissance'=>$etudiant['date_naissance'], 'classe_id'=>$etudiant['classe_id'],
        'photo'=>$etudiant['photo'],
    ];
    if (!empty($_FILES['photo']['name'])) {
        $validation = validateUploadedFile($_FILES['photo'], [
            'image/jpeg',
            'image/png',
            'image/webp'
        ]);
        
        if (!$validation['valid']) {
            flash('error', implode(' ', $validation['errors']));
            redirect('/etudiant/profil.php');
        } else {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $f = 'etu_'.$eid.'.'.$ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $appConfig['photos_path'].'/'.$f);
            $data['photo'] = $f;
        }
    }
    $etuSvc->update($eid, $data, Auth::user()['id']);
    flash('success', 'Profil mis à jour.');
    redirect('/etudiant/profil.php');
}
$pageTitle = 'Mon profil';
require __DIR__ . '/../includes/layout/header.php';
?>
<form method="post" enctype="multipart/form-data" class="card card-body">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<img src="<?= photoUrl($appConfig, $etudiant['photo']) ?>" class="rounded-circle mb-3" width="100">
<label class="form-label">Photo</label><input type="file" name="photo" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp">
<small class="text-muted d-block mb-2">Formats acceptés: JPEG, PNG, WebP. Taille max: 5MB.</small>
<label class="form-label">Email</label><input name="email" class="form-control mb-2" value="<?= e($etudiant['email']??'') ?>">
<label class="form-label">Téléphone</label><input name="telephone" class="form-control mb-2" value="<?= e($etudiant['telephone']??'') ?>">
<button class="btn btn-primary">Enregistrer</button>
</form>
<?php require __DIR__ . '/../includes/layout/footer.php';

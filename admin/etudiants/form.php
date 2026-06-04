<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);

$service = new EtudiantService($pdo);
$user = Auth::user();
$id = (int) ($_GET['id'] ?? 0);
$etudiant = $id ? $service->findActive($id) : null;

if ($id && !$etudiant) {
    flash('error', 'Étudiant introuvable.');
    redirect('/admin/etudiants/index.php');
}

$classes = $service->listClasses();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $data = [
            'nom_fr'         => trim($_POST['nom_fr'] ?? ''),
            'prenom_fr'      => trim($_POST['prenom_fr'] ?? ''),
            'nom_ar'         => trim($_POST['nom_ar'] ?? '') ?: null,
            'prenom_ar'      => trim($_POST['prenom_ar'] ?? '') ?: null,
            'identifiant'    => trim($_POST['identifiant'] ?? ''),
            'cin'            => trim($_POST['cin'] ?? '') ?: null,
            'massar'         => trim($_POST['massar'] ?? ''),
            'email'          => trim($_POST['email'] ?? '') ?: null,
            'niveau_actuel'  => trim($_POST['niveau_actuel'] ?? '') ?: null,
            'cursus'         => trim($_POST['cursus'] ?? '') ?: null,
            'telephone'      => trim($_POST['telephone'] ?? '') ?: null,
            'date_naissance' => trim($_POST['date_naissance'] ?? '') ?: null,
            'classe_id'      => (int) ($_POST['classe_id'] ?? 0) ?: null,
            'photo'          => $etudiant['photo'] ?? 'default.png',
        ];

        foreach (['nom_fr', 'prenom_fr', 'identifiant', 'massar'] as $req) {
            if ($data[$req] === '') {
                $errors[] = "Le champ « $req » est obligatoire.";
            }
        }

        if (!empty($_FILES['photo']['name'])) {
            $validation = validateUploadedFile($_FILES['photo'], [
                'image/jpeg',
                'image/png',
                'image/webp'
            ]);
            
            if (!$validation['valid']) {
                $errors = array_merge($errors, $validation['errors']);
            } else {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $filename = 'etu_' . uniqid() . '.' . $ext;
                $dest = $appConfig['photos_path'] . '/' . $filename;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    $data['photo'] = $filename;
                }
            }
        }

        if (empty($errors)) {
            try {
                if ($id) {
                    $service->update($id, $data, $user['id']);
                    flash('success', 'Étudiant mis à jour.');
                } else {
                    $service->create($data, $user['id']);
                    flash('success', 'Étudiant ajouté.');
                }
                redirect('/admin/etudiants/index.php');
            } catch (PDOException $ex) {
                $errors[] = 'Erreur : identifiant ou Massar déjà utilisé.';
            }
        }
    }
}

$pageTitle = $id ? 'Modifier étudiant' : 'Nouvel étudiant';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-4"><?= e($pageTitle) ?></h1>
<?php foreach ($errors as $err): ?>
<div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="card card-body">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nom (FR) *</label>
            <input type="text" name="nom_fr" class="form-control" required value="<?= e($etudiant['nom_fr'] ?? $_POST['nom_fr'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Prénom (FR) *</label>
            <input type="text" name="prenom_fr" class="form-control" required value="<?= e($etudiant['prenom_fr'] ?? $_POST['prenom_fr'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Nom (AR)</label>
            <input type="text" name="nom_ar" class="form-control" dir="rtl" value="<?= e($etudiant['nom_ar'] ?? $_POST['nom_ar'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Prénom (AR)</label>
            <input type="text" name="prenom_ar" class="form-control" dir="rtl" value="<?= e($etudiant['prenom_ar'] ?? $_POST['prenom_ar'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Identifiant *</label>
            <input type="text" name="identifiant" class="form-control" required value="<?= e($etudiant['identifiant'] ?? $_POST['identifiant'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Code Massar / CNE *</label>
            <input type="text" name="massar" class="form-control" required value="<?= e($etudiant['massar'] ?? $_POST['massar'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">CIN</label>
            <input type="text" name="cin" class="form-control" value="<?= e($etudiant['cin'] ?? $_POST['cin'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($etudiant['email'] ?? $_POST['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input type="text" name="telephone" class="form-control" value="<?= e($etudiant['telephone'] ?? $_POST['telephone'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Niveau actuel</label>
            <input type="text" name="niveau_actuel" class="form-control" value="<?= e($etudiant['niveau_actuel'] ?? $_POST['niveau_actuel'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Date de naissance</label>
            <input type="date" name="date_naissance" class="form-control" value="<?= e($etudiant['date_naissance'] ?? $_POST['date_naissance'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Classe</label>
            <select name="classe_id" class="form-select">
                <option value="">—</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (($etudiant['classe_id'] ?? $_POST['classe_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                    <?= e($c['filiere_alias'] . ' — ' . $c['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Cursus</label>
            <textarea name="cursus" class="form-control" rows="2"><?= e($etudiant['cursus'] ?? $_POST['cursus'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Photo</label>
            <input type="file" name="photo" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp">
            <small class="text-muted d-block mb-2">Formats acceptés: JPEG, PNG, WebP. Taille max: 5MB.</small>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="index.php" class="btn btn-link">Annuler</a>
    </div>
</form>
<?php require __DIR__ . '/../../includes/layout/footer.php';

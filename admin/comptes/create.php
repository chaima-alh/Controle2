<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$svc = new CompteService($pdo);
$personne = null;
$type = $_GET['type'] ?? $_POST['personne_type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'search') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } elseif ($type === 'etudiant') {
        $personne = $svc->findEtudiantByMassar(trim($_POST['massar'] ?? ''));
        if (!$personne) {
            flash('error', 'Personne non trouvée.');
        }
    } else {
        $personne = $svc->findEnseignantByCin(trim($_POST['cin'] ?? ''));
        if (!$personne) {
            flash('error', 'Personne non trouvée.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'create') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        try {
            $r = $svc->createCompte($_POST['role'], $_POST['personne_type'], (int)$_POST['personne_id']);
            $_SESSION['generated_credentials'] = $r;
            redirect('/admin/comptes/index.php');
        } catch (Throwable $ex) {
            flash('error', $ex->getMessage());
        }
    }
}

$pageTitle = 'Créer un compte';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Créer un compte</h1>
<form method="get" class="mb-3"><select name="type" class="form-select w-auto d-inline" onchange="this.form.submit()">
<option value="etudiant" <?= $type==='etudiant'?'selected':'' ?>>Étudiant (recherche Massar)</option>
<option value="enseignant" <?= $type==='enseignant'?'selected':'' ?>>Enseignant (recherche CIN)</option>
</select></form>

<?php if (!$personne): ?>
<form method="post" class="card card-body">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="step" value="search"><input type="hidden" name="personne_type" value="<?= e($type) ?>">
<?php if ($type === 'etudiant'): ?>
<label class="form-label">Code Massar / CNE</label><input name="massar" class="form-control" required>
<?php else: ?>
<label class="form-label">CIN enseignant</label><input name="cin" class="form-control" required>
<?php endif; ?>
<button class="btn btn-primary mt-2">Rechercher</button>
</form>
<?php else: ?>
<div class="alert alert-info">Trouvé : <strong><?= e($personne['nom_fr'].' '.$personne['prenom_fr']) ?></strong></div>
<form method="post" class="card card-body">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="step" value="create">
<input type="hidden" name="personne_type" value="<?= e($type) ?>">
<input type="hidden" name="personne_id" value="<?= (int)$personne['id'] ?>">
<label class="form-label">Rôle du compte</label>
<select name="role" class="form-select mb-3" required>
<option value="etudiant">Étudiant</option>
<option value="enseignant">Enseignant</option>
</select>
<p class="small text-muted">Login = nom+prénom (avec numéro si doublon). Mot de passe aléatoire affiché après création.</p>
<button class="btn btn-success">Créer le compte</button>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

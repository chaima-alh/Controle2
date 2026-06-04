<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
logNavigation($pdo, Auth::user()['id'], '/admin/comptes/index.php');
$svc = new CompteService($pdo);
$generated = $_SESSION['generated_credentials'] ?? null;
unset($_SESSION['generated_credentials']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
        redirect('/admin/comptes/index.php');
    }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['compte_id'] ?? 0);
    try {
        if ($action === 'create' && $id === 0) {
            $r = $svc->createCompte($_POST['role'], $_POST['personne_type'], (int)$_POST['personne_id']);
            $_SESSION['generated_credentials'] = $r;
            flash('success', 'Compte créé.');
        } elseif ($action === 'reset' && $id) {
            $r = $svc->resetPassword($id);
            $_SESSION['generated_credentials'] = ['login' => $_POST['login'] ?? '', 'password' => $r['password']];
            flash('success', 'Mot de passe réinitialisé.');
        } elseif ($action === 'toggle' && $id) {
            $svc->setEnabled($id, (bool)$_POST['enabled']);
            flash('success', 'Statut mis à jour.');
        } elseif ($action === 'role' && $id) {
            $svc->setRole($id, $_POST['new_role']);
            flash('success', 'Rôle modifié.');
        }
    } catch (Throwable $ex) {
        flash('error', $ex->getMessage());
    }
    redirect('/admin/comptes/index.php');
}

$comptes = $svc->listComptes();
$pageTitle = 'Gestion des comptes';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Gestion des comptes</h1>
<?php if ($generated): ?>
<div class="alert alert-warning"><strong>Identifiants générés :</strong> Login <code><?= e($generated['login']) ?></code> — Mot de passe <code><?= e($generated['password']) ?></code> (à communiquer une seule fois)</div>
<?php endif; ?>
<div class="mb-3">
<a href="create.php" class="btn btn-primary">Créer un compte (étudiant / enseignant)</a>
<a href="connexions.php" class="btn btn-outline-secondary">Historique connexions</a>
<a href="../enseignants/index.php" class="btn btn-outline-secondary">Gérer enseignants</a>
</div>
<table class="table table-striped bg-white">
<thead><tr><th>Login</th><th>Rôle</th><th>Personne</th><th>Actif</th><th>Verrouillé</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($comptes as $c): if ($c['login']==='admin' && $c['role']==='administrateur') continue; ?>
<tr>
<td><?= e($c['login']) ?></td><td><?= e($c['role']) ?></td><td><?= e($c['personne_nom'] ?? '—') ?></td>
<td><?= (int)$c['enabled'] ? 'Oui' : 'Non' ?></td><td><?= (int)$c['locked'] ? 'Oui' : 'Non' ?></td>
<td class="text-nowrap">
<form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="reset"><input type="hidden" name="compte_id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="login" value="<?= e($c['login']) ?>"><button class="btn btn-sm btn-warning">Reset MDP</button></form>
<form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="compte_id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="enabled" value="<?= (int)$c['enabled']?0:1 ?>"><button class="btn btn-sm btn-secondary"><?= (int)$c['enabled']?'Désactiver':'Activer' ?></button></form>
<a href="navigation.php?compte_id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-info">Pages visitées</a>
<form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="role"><input type="hidden" name="compte_id" value="<?= (int)$c['id'] ?>">
<select name="new_role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
<?php foreach (['etudiant','enseignant','administrateur'] as $r): ?><option value="<?= $r ?>" <?= $c['role']===$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?>
</select></form>
</td></tr>
<?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/../../includes/layout/footer.php';

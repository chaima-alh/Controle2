<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
logNavigation($pdo, Auth::user()['id'], '/admin/securite/ips.php');
$security = new SecurityService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'block') {
            $security->blockIp(trim($_POST['ip']), trim($_POST['raison'] ?? ''));
            flash('success', 'IP bloquée.');
        } elseif ($action === 'unblock') {
            $security->unblockIp(trim($_POST['ip']));
            flash('success', 'IP débloquée.');
        } elseif ($action === 'toggle_captcha') {
            $val = $_POST['captcha_actif'] === '1' ? '1' : '0';
            $pdo->prepare('INSERT INTO configuration (cle, valeur) VALUES (?,?) ON DUPLICATE KEY UPDATE valeur=?')
                ->execute(['captcha_actif', $val, $val]);
            flash('success', 'Captcha ' . ($val === '1' ? 'activé' : 'désactivé') . '.');
        }
    }
    redirect('/admin/securite/ips.php');
}

$blocked = $security->listBlocked();
$captchaOn = config($pdo, 'captcha_actif', '1') === '1';
$pageTitle = 'Sécurité';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Sécurité — blocage IP & captcha</h1>
<?php if ($m = flash('success')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>

<div class="card card-body mb-4">
<h2 class="h6">Captcha sur la page de connexion</h2>
<form method="post" class="d-flex align-items-center gap-2">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="action" value="toggle_captcha">
<select name="captcha_actif" class="form-select w-auto">
<option value="1" <?= $captchaOn ? 'selected' : '' ?>>Activé</option>
<option value="0" <?= !$captchaOn ? 'selected' : '' ?>>Désactivé</option>
</select>
<button class="btn btn-primary btn-sm">Enregistrer</button>
</form>
</div>

<div class="row">
<div class="col-md-5">
<div class="card card-body mb-3">
<h2 class="h6">Bloquer une adresse IP</h2>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="hidden" name="action" value="block">
<input name="ip" class="form-control mb-2" placeholder="Ex: 192.168.1.50" required>
<input name="raison" class="form-control mb-2" placeholder="Raison (optionnel)">
<button class="btn btn-danger">Bloquer</button>
</form>
<p class="small text-muted mt-2 mb-0">Votre IP actuelle : <code><?= e(SecurityService::clientIp()) ?></code></p>
</div>
</div>
<div class="col-md-7">
<table class="table bg-white"><thead><tr><th>IP</th><th>Raison</th><th>Date</th><th></th></tr></thead><tbody>
<?php foreach ($blocked as $b): ?>
<tr>
<td><?= e($b['adresse_ip']) ?></td>
<td><?= e($b['raison'] ?? '') ?></td>
<td><?= e($b['created_at']) ?></td>
<td>
<form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="unblock"><input type="hidden" name="ip" value="<?= e($b['adresse_ip']) ?>">
<button class="btn btn-sm btn-outline-success">Débloquer</button></form>
</td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php';

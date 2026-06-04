<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (Auth::check()) {
    redirect(Auth::dashboardPath());
}

$security = new SecurityService($pdo);
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

if ($security->isIpBlocked()) {
    $error = 'Votre adresse IP est bloquée. Contactez l\'administrateur.';
}

// Disable captcha display/verification for local/dev usage to avoid blocking startup
$captchaEnabled = false;
$captchaQuestion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$security->isIpBlocked()) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Token de sécurité invalide.';
    } elseif (($login = trim($_POST['login'] ?? '')) === '' || ($password = $_POST['password'] ?? '') === '') {
        $error = 'Veuillez saisir le login et le mot de passe.';
    } elseif (Auth::attempt($pdo, $login, $password, !empty($_POST['remember']))) {
        $user = Auth::user();
        logNavigation($pdo, $user['id'], '/login.php');
        redirect(Auth::dashboardPath());
    } else {
        $error = $error ?? 'Identifiants incorrects.';
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/includes/layout/header.php';
?>
<!-- EDIT_OK: login.php served -->
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h1 class="h4 mb-4 text-center">Connexion</h1>
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if (!$security->isIpBlocked()): ?>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="login">Login</label>
                        <input type="text" class="form-control" id="login" name="login" required autofocus value="<?= e($_POST['login'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <!-- Captcha disabled -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">Se souvenir de moi (14 jours)</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/layout/footer.php';

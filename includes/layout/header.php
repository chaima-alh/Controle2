<?php
/** @var string $pageTitle */
/** @var array $appConfig */
$user = Auth::user();
$base = $appConfig['base_url'];
$unread = 0;
if ($user) {
    $unread = (new NotificationService($pdo))->countUnread((int)$user['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'ENSAH Absences') ?> — <?= e($appConfig['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e($base) ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= e($base) ?>/index.php">ENSAH</a>
        <?php if ($user): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <?php if ($user['role'] === 'administrateur'): ?>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/admin/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/admin/etudiants/index.php">Étudiants</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/admin/structure/index.php">Structure</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/admin/comptes/index.php">Comptes</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/admin/absences/index.php">Absences</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/admin/securite/ips.php">Sécurité</a></li>
                <?php elseif ($user['role'] === 'enseignant'): ?>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/enseignant/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/enseignant/absences/saisie.php">Saisir absences</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/enseignant/absences/fiche.php">Fiche étudiant</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/enseignant/absences/demandes.php">Permissions</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/enseignant/etudiants/index.php">Étudiants</a></li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/etudiant/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/etudiant/absences.php">Mes absences</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/etudiant/justifier.php">Justifier</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/etudiant/reclamations.php">Réclamations</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/etudiant/permissions.php">Permissions</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>/etudiant/profil.php">Mon profil</a></li>
                <?php endif; ?>
            </ul>
            <?php if ($unread > 0 && $user['role'] === 'etudiant'): ?>
            <a href="<?= e($base) ?>/etudiant/notifications.php" class="badge bg-warning text-dark me-2"><?= $unread ?> notif.</a>
            <?php endif; ?>
            <span class="navbar-text text-white me-3 small"><?= e($user['login']) ?></span>
            <a class="btn btn-outline-light btn-sm" href="<?= e($base) ?>/logout.php">Déconnexion</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
<main class="container pb-5">

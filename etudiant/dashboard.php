<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['etudiant']);
logNavigation($pdo, Auth::user()['id'], '/etudiant/dashboard.php');
$notif = (new NotificationService($pdo))->countUnread((int)Auth::user()['id']);
$pageTitle = 'Espace étudiant';
require __DIR__ . '/../includes/layout/header.php';
?>
<h1 class="h3 mb-4">Mon espace étudiant</h1>
<?php if ($notif): ?><div class="alert alert-warning"><?= $notif ?> notification(s) non lue(s). <a href="notifications.php">Voir</a></div><?php endif; ?>
<div class="list-group col-md-6">
<a href="absences.php" class="list-group-item list-group-item-action">Ma fiche d'absences</a>
<a href="justifier.php" class="list-group-item list-group-item-action">Justifier une absence</a>
<a href="reclamations.php" class="list-group-item list-group-item-action">Réclamations</a>
<a href="permissions.php" class="list-group-item list-group-item-action">Demande de permission</a>
<a href="profil.php" class="list-group-item list-group-item-action">Email, téléphone, photo</a>
<a href="notifications.php" class="list-group-item list-group-item-action">Notifications & avertissements</a>
</div>
<?php require __DIR__ . '/../includes/layout/footer.php';

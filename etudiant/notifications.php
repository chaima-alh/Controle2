<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['etudiant']);
$notif = new NotificationService($pdo);
$cid = (int)Auth::user()['id'];
if (isset($_GET['read'])) { $notif->markRead((int)$_GET['read'], $cid); redirect('/etudiant/notifications.php'); }
$rows = $notif->listForCompte($cid);
$absSvc = new AbsenceService($pdo);
$avertissements = $absSvc->avertissementsEtudiant(personneIdFromUser(Auth::user()));
$pageTitle = 'Notifications';
require __DIR__ . '/../includes/layout/header.php';
?>
<h1 class="h4 mb-3">Notifications & avertissements</h1>
<?php foreach ($avertissements as $a): ?>
<div class="alert alert-warning"><?= e($a['message']) ?> <small>(<?= e($a['element_titre']) ?>)</small></div>
<?php endforeach; ?>
<?php foreach ($rows as $n): ?>
<div class="card mb-1 <?= $n['lu']?'':'border-primary' ?>"><div class="card-body py-2">
<strong><?= e($n['titre']) ?></strong> <?= $n['lu']?'':'<span class="badge bg-primary">nouveau</span>' ?>
<p class="mb-0 small"><?= e($n['message']) ?></p>
<?php if (!$n['lu']): ?><a href="?read=<?= (int)$n['id'] ?>" class="small">Marquer lu</a><?php endif; ?>
</div></div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/layout/footer.php';

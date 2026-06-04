<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['enseignant']);
$ensId = personneIdFromUser(Auth::user());
if (!$ensId) exit('Compte non lié.');
$absSvc = new AbsenceService($pdo);
if (isset($_GET['id'], $_GET['reponse'])) {
    $absSvc->traiterDemande((int)$_GET['id'], $_GET['reponse'] === 'ok' ? 'acceptee' : 'refusee');
    flash('success', 'Demande traitée. Notification envoyée à l\'étudiant.');
    redirect('/enseignant/absences/demandes.php');
}
$rows = $absSvc->demandesPourEnseignant($ensId);
$pageTitle = 'Demandes de permission';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h4 mb-3">Demandes de permission d'absence</h1>
<?php foreach ($rows as $d): if ($d['statut'] !== 'en_attente') continue; ?>
<div class="card mb-2"><div class="card-body">
<strong><?= e($d['nom_fr'].' '.$d['prenom_fr']) ?></strong>
<p><?= e($d['message']) ?></p>
<p class="small text-muted"><?= e($d['element_titre'] ?? '—') ?> — <?= e($d['created_at']) ?></p>
<a href="?id=<?= (int)$d['id'] ?>&reponse=ok" class="btn btn-success btn-sm">OK</a>
<a href="?id=<?= (int)$d['id'] ?>&reponse=non" class="btn btn-danger btn-sm">Non</a>
</div></div>
<?php endforeach; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

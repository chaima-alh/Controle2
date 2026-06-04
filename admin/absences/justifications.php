<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
$absSvc = new AbsenceService($pdo);
if (isset($_GET['traiter'], $_GET['statut'])) {
    $absSvc->traiterJustification((int)$_GET['traiter'], $_GET['statut'] === 'acceptee' ? 'acceptee' : 'refusee');
    flash('success', 'Justification traitée.');
    redirect('/admin/absences/justifications.php');
}
$rows = $absSvc->listJustificationsEnAttente();
$pageTitle = 'Justifications';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Justifications en attente</h1>
<table class="table bg-white"><thead><tr><th>Étudiant</th><th>Date absence</th><th>Élément</th><th>Fichier</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['nom_fr'].' '.$r['prenom_fr']) ?></td><td><?= e($r['date_heure']) ?></td><td><?= e($r['element_titre']) ?></td>
<td><a href="<?= e($appConfig['base_url']) ?>/uploads/justifications/<?= e($r['fichier']) ?>" target="_blank">Voir</a></td>
<td>
<a href="?traiter=<?= (int)$r['id'] ?>&statut=acceptee" class="btn btn-sm btn-success">Accepter</a>
<a href="?traiter=<?= (int)$r['id'] ?>&statut=refusee" class="btn btn-sm btn-danger">Refuser</a>
</td></tr>
<?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/../../includes/layout/footer.php';

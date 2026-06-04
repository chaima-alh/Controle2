<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur', 'enseignant']);

$service = new EtudiantService($pdo);
$id = (int) ($_GET['id'] ?? 0);
$etudiant = $service->findActive($id);

if (!$etudiant) {
    flash('error', 'Étudiant introuvable.');
    redirect(Auth::role() === 'administrateur' ? '/admin/etudiants/index.php' : '/enseignant/etudiants/index.php');
}

$audit = Auth::role() === 'administrateur' ? $service->getAudit($id) : [];

$pageTitle = 'Fiche étudiant';
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="row">
    <div class="col-md-4 text-center mb-3">
        <img src="<?= e($appConfig['base_url']) ?>/uploads/photos/<?= e($etudiant['photo'] ?? 'default.png') ?>"
             class="img-thumbnail rounded-circle" style="width:150px;height:150px;object-fit:cover"
             onerror="this.src='<?= e($appConfig['base_url']) ?>/assets/img/default.png'">
    </div>
    <div class="col-md-8">
        <h1 class="h4"><?= e($etudiant['nom_fr'] . ' ' . $etudiant['prenom_fr']) ?></h1>
        <dl class="row">
            <dt class="col-sm-4">Identifiant</dt><dd class="col-sm-8"><?= e($etudiant['identifiant']) ?></dd>
            <dt class="col-sm-4">Massar</dt><dd class="col-sm-8"><?= e($etudiant['massar']) ?></dd>
            <dt class="col-sm-4">CIN</dt><dd class="col-sm-8"><?= e($etudiant['cin'] ?? '—') ?></dd>
            <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($etudiant['email'] ?? '—') ?></dd>
            <dt class="col-sm-4">Téléphone</dt><dd class="col-sm-8"><?= e($etudiant['telephone'] ?? '—') ?></dd>
            <dt class="col-sm-4">Classe</dt><dd class="col-sm-8"><?= e($etudiant['classe_nom'] ?? '—') ?></dd>
            <dt class="col-sm-4">Niveau</dt><dd class="col-sm-8"><?= e($etudiant['niveau_actuel'] ?? '—') ?></dd>
        </dl>
        <a href="<?= e($appConfig['base_url']) ?>/admin/absences/fiche.php?etudiant_id=<?= $id ?>" class="btn btn-outline-primary">
            Fiche d'absences
        </a>
        <?php if (Auth::role() === 'administrateur'): ?>
        <a href="form.php?id=<?= $id ?>" class="btn btn-secondary">Modifier</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($audit): ?>
<h2 class="h5 mt-4">Historique des modifications</h2>
<table class="table table-sm">
    <thead><tr><th>Date</th><th>Champ</th><th>Ancien</th><th>Nouveau</th><th>Par</th></tr></thead>
    <tbody>
    <?php foreach ($audit as $a): ?>
    <tr>
        <td><?= e($a['modifie_le']) ?></td>
        <td><?= e($a['champ']) ?></td>
        <td><?= e($a['ancienne_valeur'] ?? '') ?></td>
        <td><?= e($a['nouvelle_valeur'] ?? '') ?></td>
        <td><?= e($a['login'] ?? 'système') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

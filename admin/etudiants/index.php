<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);

$service = new EtudiantService($pdo);
$user = Auth::user();
logNavigation($pdo, $user['id'], '/admin/etudiants/index.php');

$filters = [
    'nom'        => trim($_GET['nom'] ?? ''),
    'phonetique' => trim($_GET['phonetique'] ?? ''),
    'massar'     => trim($_GET['massar'] ?? ''),
    'identifiant'=> trim($_GET['identifiant'] ?? ''),
    'classe_id'  => (int) ($_GET['classe_id'] ?? 0) ?: null,
];

$etudiants = $service->search($filters);
$classes = $service->listClasses();
$corbeille = isset($_GET['corbeille']);

if ($corbeille) {
    $etudiants = $service->getDeleted();
}

$pageTitle = 'Étudiants';
require __DIR__ . '/../../includes/layout/header.php';

$success = flash('success');
$error = flash('error');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 no-print">
    <h1 class="h3 mb-0"><?= $corbeille ? 'Corbeille' : 'Liste des étudiants' ?></h1>
    <div class="d-flex gap-2">
        <?php if (!$corbeille): ?>
        <a href="stats.php" class="btn btn-outline-info">Statistiques / classe</a>
        <a href="form.php" class="btn btn-primary">Ajouter</a>
        <a href="export.php?<?= http_build_query($filters) ?>" class="btn btn-outline-secondary">Exporter CSV</a>
        <?php if (!empty($filters['classe_id'])): ?>
        <a href="print.php?classe_id=<?= (int) $filters['classe_id'] ?>" class="btn btn-outline-secondary" target="_blank">Imprimer</a>
        <?php endif; ?>
        <a href="?corbeille=1" class="btn btn-outline-warning">Corbeille</a>
        <?php else: ?>
        <a href="index.php" class="btn btn-secondary">Retour liste</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (!$corbeille): ?>
<form class="card card-body mb-3 no-print" method="get">
    <div class="row g-2">
        <div class="col-md-3">
            <input type="text" name="nom" class="form-control" placeholder="Nom / prénom" value="<?= e($filters['nom']) ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="phonetique" class="form-control" placeholder="Recherche phonétique" value="<?= e($filters['phonetique']) ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="massar" class="form-control" placeholder="Massar / CNE" value="<?= e($filters['massar']) ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="identifiant" class="form-control" placeholder="Identifiant" value="<?= e($filters['identifiant']) ?>">
        </div>
        <div class="col-md-2">
            <select name="classe_id" class="form-select">
                <option value="">Toutes les classes</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= ($filters['classe_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['filiere_alias'] . ' — ' . $c['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
        </div>
    </div>
</form>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover bg-white">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Identifiant</th>
                <th>Massar</th>
                <th>Nom & prénom</th>
                <th>Classe</th>
                <th class="no-print">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($etudiants)): ?>
            <tr><td colspan="6" class="text-center text-muted">Aucun résultat</td></tr>
        <?php else: ?>
            <?php foreach ($etudiants as $e): ?>
            <tr>
                <td>
                    <img src="<?= e($appConfig['base_url']) ?>/uploads/photos/<?= e($e['photo'] ?? 'default.png') ?>"
                         alt="" class="student-photo" onerror="this.src='<?= e($appConfig['base_url']) ?>/assets/img/default.png'">
                </td>
                <td><?= e($e['identifiant']) ?></td>
                <td><?= e($e['massar']) ?></td>
                <td><?= e($e['nom_fr'] . ' ' . $e['prenom_fr']) ?></td>
                <td><?= e($e['classe_nom'] ?? '—') ?></td>
                <td class="no-print">
                    <?php if ($corbeille): ?>
                    <a href="restore.php?id=<?= (int) $e['id'] ?>" class="btn btn-sm btn-success"
                       onclick="return confirm('Restaurer cet étudiant ?')">Restaurer</a>
                    <?php else: ?>
                    <a href="view.php?id=<?= (int) $e['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                    <a href="form.php?id=<?= (int) $e['id'] ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                    <a href="delete.php?id=<?= (int) $e['id'] ?>" class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Placer dans la corbeille ?')">Supprimer</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php';

<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['enseignant']);

$service = new EtudiantService($pdo);
logNavigation($pdo, Auth::user()['id'], '/enseignant/etudiants/index.php');

$filters = [
    'nom'        => trim($_GET['nom'] ?? ''),
    'phonetique' => trim($_GET['phonetique'] ?? ''),
    'massar'     => trim($_GET['massar'] ?? ''),
    'identifiant'=> trim($_GET['identifiant'] ?? ''),
    'classe_id'  => (int) ($_GET['classe_id'] ?? 0) ?: null,
];

$etudiants = $service->search($filters);
$classes = $service->listClasses();

$pageTitle = 'Étudiants';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Recherche d'étudiants</h1>

<form class="card card-body mb-3" method="get">
    <div class="row g-2">
        <div class="col-md-3"><input type="text" name="nom" class="form-control" placeholder="Nom" value="<?= e($filters['nom']) ?>"></div>
        <div class="col-md-2"><input type="text" name="phonetique" class="form-control" placeholder="Phonétique" value="<?= e($filters['phonetique']) ?>"></div>
        <div class="col-md-2"><input type="text" name="massar" class="form-control" placeholder="Massar" value="<?= e($filters['massar']) ?>"></div>
        <div class="col-md-2"><input type="text" name="identifiant" class="form-control" placeholder="Identifiant" value="<?= e($filters['identifiant']) ?>"></div>
        <div class="col-md-2">
            <select name="classe_id" class="form-select">
                <option value="">Classe</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= ($filters['classe_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-primary w-100">OK</button></div>
    </div>
</form>

<div class="mb-2">
    <a href="<?= e($appConfig['base_url']) ?>/admin/etudiants/export.php?<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-secondary">Exporter CSV</a>
</div>

<table class="table table-striped bg-white">
    <thead><tr><th>Identifiant</th><th>Massar</th><th>Nom</th><th>Classe</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($etudiants as $e): ?>
    <tr>
        <td><?= e($e['identifiant']) ?></td>
        <td><?= e($e['massar']) ?></td>
        <td><?= e($e['nom_fr'] . ' ' . $e['prenom_fr']) ?></td>
        <td><?= e($e['classe_nom'] ?? '—') ?></td>
        <td><a href="<?= e($appConfig['base_url']) ?>/admin/etudiants/view.php?id=<?= (int) $e['id'] ?>">Fiche</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../../includes/layout/footer.php';

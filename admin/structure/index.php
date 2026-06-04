<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
logNavigation($pdo, Auth::user()['id'], '/admin/structure/index.php');
$svc = new StructureService($pdo);
$pageTitle = 'Structure pédagogique';
$section = $_GET['s'] ?? 'filieres';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
        redirect('/admin/structure/index.php');
    }
    $action = $_POST['action'] ?? '';
    try {
        match ($action) {
            'save_filiere' => $svc->saveFiliere($_POST, (int)($_POST['id'] ?? 0) ?: null),
            'del_filiere' => $svc->deleteFiliere((int)$_POST['id']),
            'save_classe' => $svc->saveClasse($_POST, (int)($_POST['id'] ?? 0) ?: null),
            'del_classe' => $svc->deleteClasse((int)$_POST['id']),
            'save_module' => $svc->saveModule($_POST, (int)($_POST['id'] ?? 0) ?: null),
            'del_module' => $svc->deleteModule((int)$_POST['id']),
            'save_element' => $svc->saveElement($_POST, (int)($_POST['id'] ?? 0) ?: null),
            'del_element' => $svc->deleteElement((int)$_POST['id']),
            'assoc_mc' => $svc->associerModuleClasse((int)$_POST['classe_id'], (int)$_POST['module_id']),
            'dissoc_mc' => $svc->dissocierModuleClasse((int)$_POST['classe_id'], (int)$_POST['module_id']),
            default => null,
        };
        flash('success', 'Enregistré.');
    } catch (Throwable $ex) {
        flash('error', $ex->getMessage());
    }
    redirect('/admin/structure/index.php?s=' . urlencode($section));
}

if ($section === 'import' && isset($_FILES['fichier'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', 'Token de sécurité invalide.');
    } else {
        $tmp = $_FILES['fichier']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
        try {
            $r = $svc->importFile($tmp, $ext);
            flash($r['ok'] ? 'success' : 'error', $r['message']);
        } catch (Throwable $ex) {
            flash('error', $ex->getMessage());
        }
    }
    redirect('/admin/structure/index.php?s=import');
}

require __DIR__ . '/../../includes/layout/header.php';
$success = flash('success'); $error = flash('error');
?>
<h1 class="h3 mb-3">Structure pédagogique</h1>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<ul class="nav nav-tabs mb-3">
    <?php foreach (['filieres'=>'Filières','classes'=>'Classes','modules'=>'Modules','elements'=>'Éléments','assoc'=>'Associations','import'=>'Import CSV','classe_mod'=>'Modules/classe'] as $k=>$l): ?>
    <li class="nav-item"><a class="nav-link <?= $section===$k?'active':'' ?>" href="?s=<?= $k ?>"><?= $l ?></a></li>
    <?php endforeach; ?>
</ul>

<?php if ($section === 'filieres'): $rows = $svc->listFilieres(); $ens = $svc->listEnseignants(); ?>
<div class="row">
<div class="col-md-5"><div class="card card-body">
<h2 class="h6">Ajouter / modifier filière</h2>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="save_filiere">
<input type="hidden" name="id" id="f_id">
<div class="mb-2"><label class="form-label">Alias (GI1…)</label><input name="alias" id="f_alias" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Intitulé</label><input name="intitule" id="f_intitule" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Accréditation début</label><input type="number" name="annee_debut" id="f_debut" class="form-control"></div>
<div class="mb-2"><label class="form-label">Accréditation fin</label><input type="number" name="annee_fin" id="f_fin" class="form-control"></div>
<div class="mb-2"><label class="form-label">Coordonnateur</label>
<select name="coordinateur_id" id="f_coord" class="form-select"><option value="">—</option>
<?php foreach ($ens as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e($e['nom_fr'].' '.$e['prenom_fr']) ?></option><?php endforeach; ?>
</select></div>
<button class="btn btn-primary">Enregistrer</button>
</form></div></div>
<div class="col-md-7"><table class="table table-sm bg-white"><thead><tr><th>Alias</th><th>Intitulé</th><th>Coord.</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['alias']) ?></td><td><?= e($r['intitule']) ?></td><td><?= e(trim(($r['coord_nom']??'').' '.($r['coord_prenom']??''))) ?></td>
<td><button type="button" class="btn btn-sm btn-outline-secondary" onclick='editF(<?= json_encode($r, JSON_HEX_APOS) ?>)'>Mod.</button>
<form method="post" class="d-inline" onsubmit="return confirm('Supprimer ?')"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="del_filiere"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger">×</button></form></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<script>function editF(r){document.getElementById('f_id').value=r.id;document.getElementById('f_alias').value=r.alias;document.getElementById('f_intitule').value=r.intitule;document.getElementById('f_debut').value=r.annee_accreditation_debut||'';document.getElementById('f_fin').value=r.annee_accreditation_fin||'';document.getElementById('f_coord').value=r.coordinateur_id||'';}</script>

<?php elseif ($section === 'classes'): $rows = $svc->listClasses(); $filieres = $svc->listFilieres(); ?>
<form method="post" class="card card-body mb-3"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="save_classe"><input type="hidden" name="id" id="c_id">
<div class="row g-2"><div class="col-md-3"><select name="filiere_id" id="c_filiere" class="form-select" required><?php foreach ($filieres as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e($f['alias']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><input name="nom" id="c_nom" class="form-control" placeholder="Nom classe" required></div>
<div class="col-md-3"><input name="niveau" id="c_niveau" class="form-control" placeholder="Niveau" required></div>
<div class="col-md-3"><button class="btn btn-primary w-100">Enregistrer classe</button></div></div></form>
<table class="table bg-white"><thead><tr><th>Filière</th><th>Classe</th><th>Niveau</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['filiere_alias']) ?></td><td><?= e($r['nom']) ?></td><td><?= e($r['niveau']) ?></td>
<td><form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="del_classe"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger" onclick="return confirm('OK?')">×</button></form></td></tr><?php endforeach; ?>
</tbody></table>

<?php elseif ($section === 'modules'): $rows = $svc->listModules(); ?>
<form method="post" class="card card-body mb-3 row g-2"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="save_module"><input type="hidden" name="id" id="m_id">
<div class="col-md-3"><input name="code" id="m_code" class="form-control" placeholder="Code" required></div>
<div class="col-md-5"><input name="titre" id="m_titre" class="form-control" placeholder="Titre" required></div>
<div class="col-md-2"><input name="niveau" id="m_niveau" class="form-control" placeholder="Niveau" required></div>
<div class="col-md-2"><button class="btn btn-primary w-100">OK</button></div></form>
<table class="table bg-white"><thead><tr><th>Code</th><th>Titre</th><th>Niveau</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['code']) ?></td><td><?= e($r['titre']) ?></td><td><?= e($r['niveau']) ?></td>
<td><form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="del_module"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">×</button></form></td></tr><?php endforeach; ?>
</tbody></table>

<?php elseif ($section === 'elements'): $rows = $svc->listElements(); $mods = $svc->listModules(); ?>
<form method="post" class="card card-body mb-3 row g-2"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="save_element"><input type="hidden" name="id" id="el_id">
<div class="col-md-4"><select name="module_id" class="form-select" required><?php foreach ($mods as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['code'].' — '.$m['titre']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><input name="code" class="form-control" placeholder="Code élément" required></div>
<div class="col-md-3"><input name="titre" class="form-control" placeholder="Titre" required></div>
<div class="col-md-2"><button class="btn btn-primary w-100">OK</button></div></form>
<table class="table bg-white"><thead><tr><th>Module</th><th>Code</th><th>Titre</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['module_code']) ?></td><td><?= e($r['code']) ?></td><td><?= e($r['titre']) ?></td>
<td><form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="del_element"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">×</button></form></td></tr><?php endforeach; ?>
</tbody></table>

<?php elseif ($section === 'assoc' || $section === 'classe_mod'):
    $classeId = (int)($_GET['classe_id'] ?? 0);
    $classes = $svc->listClasses();
    $mods = $classeId ? $svc->modulesByClasse($classeId) : [];
?>
<form class="mb-3" method="get"><input type="hidden" name="s" value="classe_mod">
<select name="classe_id" class="form-select" onchange="this.form.submit()"><option value="">Choisir une classe</option>
<?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $classeId===(int)$c['id']?'selected':'' ?>><?= e($c['filiere_alias'].' — '.$c['nom']) ?></option><?php endforeach; ?>
</select></form>
<?php if ($classeId): ?>
<h2 class="h6">Modules de la classe</h2><ul><?php foreach ($mods as $m): ?><li><?= e($m['code'].' — '.$m['titre']) ?>
<form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="dissoc_mc"><input type="hidden" name="classe_id" value="<?= $classeId ?>"><input type="hidden" name="module_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-link btn-sm text-danger p-0">retirer</button></form></li><?php endforeach; ?></ul>
<form method="post" class="row g-2"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="assoc_mc"><input type="hidden" name="classe_id" value="<?= $classeId ?>">
<div class="col-md-8"><select name="module_id" class="form-select"><?php foreach ($svc->listModules() as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['code']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><button class="btn btn-primary w-100">Associer module</button></div></form>
<?php endif; ?>

<?php elseif ($section === 'import'): ?>
<div class="card card-body">
<p><strong>Formats acceptés :</strong> CSV, XML, XLSX (Excel)</p>
<ul class="small">
<li><strong>CSV</strong> (<code>;</code>) : <code>filiere_alias;filiere_intitule;classe_nom;classe_niveau;module_code;module_titre;module_niveau;element_code;element_titre</code></li>
<li><strong>XML</strong> : structure hiérarchique filière → classe → module → element (<a href="<?= e($appConfig['base_url']) ?>/database/exemple_structure.xml" download>exemple XML</a>)</li>
<li><strong>XLSX</strong> : mêmes colonnes que le CSV sur la 1ère feuille</li>
</ul>
<form method="post" enctype="multipart/form-data" action="?s=import">
<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<input type="file" name="fichier" accept=".csv,.xml,.xlsx" class="form-control mb-2" required>
<button class="btn btn-primary">Importer</button></form>
<p class="small text-muted mt-2">CSV exemple : <code>GI;Génie Info;GI-2A;2A;WEB101;Développement Web;2A;PHP;PHP avancé</code></p>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php';

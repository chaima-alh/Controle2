<?php
/** @var PDO $pdo @var array $appConfig @var int $etudiantId @var bool $canEdit @var bool $adminMode */
$absSvc = new AbsenceService($pdo);
$etuSvc = new EtudiantService($pdo);
$etudiant = $etuSvc->findActive($etudiantId);
if (!$etudiant) {
    echo '<div class="alert alert-warning">Étudiant introuvable.</div>';
    return;
}
$anneeId = (int) ($_GET['annee_id'] ?? anneeCouranteId($pdo));
$annees = listAnnees($pdo);
$absences = $absSvc->ficheEtudiant($etudiantId, $anneeId);
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="h5 mb-0">Fiche d'absences — <?= e($etudiant['nom_fr'] . ' ' . $etudiant['prenom_fr']) ?></h2>
    <form method="get" class="d-flex gap-2">
        <?php foreach ($_GET as $k => $v): if ($k === 'annee_id') continue; ?>
        <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
        <?php endforeach; ?>
        <select name="annee_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($annees as $a): ?>
            <option value="<?= (int)$a['id'] ?>" <?= $anneeId === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<table class="table table-sm table-striped bg-white">
    <thead>
        <tr><th>Date</th><th>Élément</th><th>Séance</th><th>État</th><th>Enseignant</th><?php if ($canEdit ?? false): ?><th>Actions</th><?php endif; ?></tr>
    </thead>
    <tbody>
    <?php if (empty($absences)): ?>
        <tr><td colspan="6" class="text-muted text-center">Aucune absence</td></tr>
    <?php else: foreach ($absences as $a): ?>
        <tr>
            <td><?= e(date('d/m/Y H:i', strtotime($a['date_heure']))) ?></td>
            <td><?= e($a['element_titre']) ?></td>
            <td><?= e($a['type_seance']) ?></td>
            <td><span class="badge bg-<?= $a['etat']==='justifiee'?'success':($a['etat']==='annulee'?'secondary':'danger') ?>"><?= e($a['etat']) ?></span></td>
            <td><?= e($a['ens_nom'].' '.$a['ens_prenom']) ?></td>
            <?php if ($canEdit ?? false): ?>
            <td class="text-nowrap">
                <?php if (!empty($adminMode)): ?>
                <a href="<?= e($appConfig['base_url']) ?>/admin/absences/edit.php?id=<?= (int)$a['id'] ?>&etudiant_id=<?= $etudiantId ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                <?php endif; ?>
                <?php if ($a['etat'] !== 'annulee'): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['annuler'=>(int)$a['id']])) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Annuler ?')">Annuler</a>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

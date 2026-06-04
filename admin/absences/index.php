<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);
logNavigation($pdo, Auth::user()['id'], '/admin/absences/index.php');
$absSvc = new AbsenceService($pdo);
$pageTitle = 'Gestion absences';
require __DIR__ . '/../../includes/layout/header.php';
?>
<h1 class="h3 mb-3">Gestion des absences (administrateur)</h1>
<div class="list-group">
<a href="fiche.php" class="list-group-item list-group-item-action">Consulter fiche d'absences d'un étudiant</a>
<a href="saisie_groupe.php" class="list-group-item list-group-item-action">Saisie groupée (liste Massar séparés par virgule)</a>
<a href="justifications.php" class="list-group-item list-group-item-action">Justifications en attente (accepter / refuser)</a>
<a href="reclamations.php" class="list-group-item list-group-item-action">Réclamations étudiants</a>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php';

<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireRole(['etudiant']);
$eid = personneIdFromUser(Auth::user());
if (!$eid) exit('Compte non lié.');
logNavigation($pdo, Auth::user()['id'], '/etudiant/absences.php');
$etudiantId = $eid;
$canEdit = false;
$pageTitle = 'Mes absences';
require __DIR__ . '/../includes/layout/header.php';
require __DIR__ . '/../includes/fiche_absences.php';
require __DIR__ . '/../includes/layout/footer.php';

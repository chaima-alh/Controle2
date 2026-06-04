<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur']);

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $service = new EtudiantService($pdo);
    $service->softDelete($id, Auth::user()['id']);
    flash('success', 'Étudiant déplacé dans la corbeille (récupération possible).');
}
redirect('/admin/etudiants/index.php');

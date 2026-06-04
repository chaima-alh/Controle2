<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur', 'enseignant']);

$service = new EtudiantService($pdo);
$filters = [
    'nom'        => trim($_GET['nom'] ?? ''),
    'phonetique' => trim($_GET['phonetique'] ?? ''),
    'massar'     => trim($_GET['massar'] ?? ''),
    'identifiant'=> trim($_GET['identifiant'] ?? ''),
    'classe_id'  => (int) ($_GET['classe_id'] ?? 0) ?: null,
];
$rows = $service->search($filters);
$service->exportCsv($rows);

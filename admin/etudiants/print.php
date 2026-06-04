<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireRole(['administrateur', 'enseignant']);

$classeId = (int) ($_GET['classe_id'] ?? 0);
if (!$classeId) {
    exit('Classe requise.');
}

$service = new EtudiantService($pdo);
$etudiants = $service->search(['classe_id' => $classeId]);

$stmt = $pdo->prepare('SELECT nom FROM classes WHERE id = ?');
$stmt->execute([$classeId]);
$classeNom = $stmt->fetchColumn() ?: 'Classe';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste — <?= htmlspecialchars($classeNom) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none; } }</style>
</head>
<body class="p-4" onload="window.print()">
<button class="btn btn-primary no-print mb-3" onclick="window.print()">Imprimer</button>
<h1 class="h4">Liste des étudiants — <?= htmlspecialchars($classeNom) ?></h1>
<p class="text-muted"><?= date('d/m/Y H:i') ?></p>
<table class="table table-bordered">
    <thead>
        <tr><th>#</th><th>Identifiant</th><th>Massar</th><th>Nom</th><th>Prénom</th><th>Email</th></tr>
    </thead>
    <tbody>
    <?php foreach ($etudiants as $i => $e): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($e['identifiant']) ?></td>
        <td><?= htmlspecialchars($e['massar']) ?></td>
        <td><?= htmlspecialchars($e['nom_fr']) ?></td>
        <td><?= htmlspecialchars($e['prenom_fr']) ?></td>
        <td><?= htmlspecialchars($e['email'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>

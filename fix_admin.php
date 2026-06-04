<?php

declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$newPassword = 'admin';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$pdo->prepare('UPDATE comptes SET mot_de_passe = ?, tentatives_echouees = 0, locked = 0, enabled = 1 WHERE login = ? AND role = ?')
    ->execute([$hash, 'admin', 'administrateur']);

$stmt = $pdo->prepare('SELECT id,login,enabled,locked,tentatives_echouees, mot_de_passe FROM comptes WHERE login = ? LIMIT 1');
$stmt->execute(['admin']);
$compte = $stmt->fetch();
if (!$compte) {
    echo 'Admin non trouvé.';
    exit;
}

echo '<pre>';
echo 'ID: ' . $compte['id'] . "\n";
echo 'LOGIN: ' . $compte['login'] . "\n";
echo 'ENABLED: ' . $compte['enabled'] . "\n";
echo 'LOCKED: ' . $compte['locked'] . "\n";
echo 'ATTEMPTS: ' . $compte['tentatives_echouees'] . "\n";
echo 'PASSWORD VERIFY: ' . (password_verify($newPassword, $compte['mot_de_passe']) ? 'OK' : 'FAIL') . "\n";
echo '</pre>';

echo 'Mot de passe admin réglé sur admin. Connectez-vous maintenant.';

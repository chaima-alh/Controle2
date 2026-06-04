<?php
declare(strict_types=1);
require __DIR__ . '/includes/Database.php';
$config = require __DIR__ . '/config/database.php';
try {
    $pdo = Database::getConnection($config);
} catch (Throwable $e) {
    echo "DBERR:" . $e->getMessage() . PHP_EOL;
    exit(1);
}
$st = $pdo->prepare('SELECT id, login, mot_de_passe, enabled, locked, tentatives_echouees FROM comptes WHERE login = ? LIMIT 1');
$st->execute(['admin']);
$r = $st->fetch(PDO::FETCH_ASSOC);
if (!$r) {
    echo "NO_ADMIN\n";
    exit(0);
}
echo "ID:" . $r['id'] . PHP_EOL;
echo "LOGIN:" . $r['login'] . PHP_EOL;
echo "ENABLED:" . $r['enabled'] . "\n";
echo "LOCKED:" . $r['locked'] . "\n";
echo "ATT:" . $r['tentatives_echouees'] . "\n";
echo "HASH:" . $r['mot_de_passe'] . "\n";
echo "VERIFY:" . (password_verify('admin', $r['mot_de_passe']) ? 'OK' : 'FAIL') . PHP_EOL;

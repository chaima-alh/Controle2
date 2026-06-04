<?php

declare(strict_types=1);

class SecurityService
{
    public function __construct(private PDO $pdo) {}

    public static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function isIpBlocked(?string $ip = null): bool
    {
        $ip = $ip ?? self::clientIp();
        $st = $this->pdo->prepare('SELECT id FROM ips_bloquees WHERE adresse_ip=? AND actif=1');
        $st->execute([$ip]);
        return (bool) $st->fetch();
    }

    public function blockIp(string $ip, string $raison = ''): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO ips_bloquees (adresse_ip, raison, actif) VALUES (?,?,1)
             ON DUPLICATE KEY UPDATE raison=VALUES(raison), actif=1'
        );
        $st->execute([$ip, $raison]);
    }

    public function unblockIp(string $ip): void
    {
        $this->pdo->prepare('UPDATE ips_bloquees SET actif=0 WHERE adresse_ip=?')->execute([$ip]);
    }

    public function listBlocked(): array
    {
        return $this->pdo->query(
            'SELECT * FROM ips_bloquees WHERE actif=1 ORDER BY created_at DESC'
        )->fetchAll();
    }
}

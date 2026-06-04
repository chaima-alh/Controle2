<?php

declare(strict_types=1);

class NotificationService
{
    public function __construct(private PDO $pdo) {}

    public function send(int $compteId, string $titre, string $message): void
    {
        $st = $this->pdo->prepare('INSERT INTO notifications (compte_id, titre, message) VALUES (?,?,?)');
        $st->execute([$compteId, $titre, $message]);
    }

    public function sendToEtudiant(int $etudiantId, string $titre, string $message): void
    {
        $st = $this->pdo->prepare(
            'SELECT id FROM comptes WHERE personne_type="etudiant" AND personne_id=? LIMIT 1'
        );
        $st->execute([$etudiantId]);
        $compteId = $st->fetchColumn();
        if ($compteId) {
            $this->send((int) $compteId, $titre, $message);
        }
    }

    public function listForCompte(int $compteId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM notifications WHERE compte_id=? ORDER BY created_at DESC');
        $st->execute([$compteId]);
        return $st->fetchAll();
    }

    public function markRead(int $id, int $compteId): void
    {
        $this->pdo->prepare('UPDATE notifications SET lu=1 WHERE id=? AND compte_id=?')->execute([$id, $compteId]);
    }

    public function countUnread(int $compteId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE compte_id=? AND lu=0');
        $st->execute([$compteId]);
        return (int) $st->fetchColumn();
    }
}

<?php

declare(strict_types=1);

class CompteService
{
    public function __construct(private PDO $pdo) {}

    public function listComptes(): array
    {
        return $this->pdo->query(
            'SELECT c.*,
                CASE c.personne_type
                    WHEN "etudiant" THEN CONCAT(e.nom_fr, " ", e.prenom_fr)
                    WHEN "enseignant" THEN CONCAT(en.nom_fr, " ", en.prenom_fr)
                    ELSE "—"
                END AS personne_nom
             FROM comptes c
             LEFT JOIN etudiants e ON c.personne_type="etudiant" AND c.personne_id=e.id
             LEFT JOIN enseignants en ON c.personne_type="enseignant" AND c.personne_id=en.id
             ORDER BY c.login'
        )->fetchAll();
    }

    public function findEtudiantByMassar(string $massar): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM etudiants WHERE massar=? AND deleted_at IS NULL');
        $st->execute([$massar]);
        return $st->fetch() ?: null;
    }

    public function findEnseignantByCin(string $cin): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM enseignants WHERE cin=?');
        $st->execute([$cin]);
        return $st->fetch() ?: null;
    }

    public function createCompte(string $role, string $personneType, int $personneId): array
    {
        $personne = $this->getPersonne($personneType, $personneId);
        if (!$personne) {
            throw new RuntimeException('Personne introuvable.');
        }
        $st = $this->pdo->prepare('SELECT id FROM comptes WHERE personne_type=? AND personne_id=?');
        $st->execute([$personneType, $personneId]);
        if ($st->fetch()) {
            throw new RuntimeException('Cette personne a déjà un compte.');
        }
        $login = generateLogin($personne['nom_fr'], $personne['prenom_fr'], $this->pdo);
        $plainPassword = randomPassword(10);
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $st = $this->pdo->prepare(
            'INSERT INTO comptes (login, mot_de_passe, role, personne_type, personne_id, enabled, locked)
             VALUES (?,?,?,?,?,1,0)'
        );
        $st->execute([$login, $hash, $role, $personneType, $personneId]);
        return ['login' => $login, 'password' => $plainPassword, 'id' => (int) $this->pdo->lastInsertId()];
    }

    public function resetPassword(int $compteId): array
    {
        $plain = randomPassword(10);
        $hash = password_hash($plain, PASSWORD_DEFAULT);
        $this->pdo->prepare('UPDATE comptes SET mot_de_passe=?, locked=0, tentatives_echouees=0 WHERE id=?')
            ->execute([$hash, $compteId]);
        return ['password' => $plain];
    }

    public function setEnabled(int $compteId, bool $enabled): void
    {
        $this->pdo->prepare('UPDATE comptes SET enabled=? WHERE id=?')->execute([(int) $enabled, $compteId]);
    }

    public function setRole(int $compteId, string $role): void
    {
        $this->pdo->prepare('UPDATE comptes SET role=? WHERE id=?')->execute([$role, $compteId]);
    }

    public function connexionsHistorique(): array
    {
        return $this->pdo->query(
            'SELECT h.*, c.login, c.role FROM connexions_historique h
             JOIN comptes c ON c.id = h.compte_id ORDER BY h.connecte_le DESC LIMIT 500'
        )->fetchAll();
    }

    public function navigationByCompte(int $compteId): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM navigation_log WHERE compte_id=? ORDER BY visite_le DESC LIMIT 200'
        );
        $st->execute([$compteId]);
        return $st->fetchAll();
    }

    public function saveEnseignant(array $d, ?int $id = null): int
    {
        if ($id) {
            $st = $this->pdo->prepare(
                'UPDATE enseignants SET nom_fr=?, prenom_fr=?, nom_ar=?, prenom_ar=?, cin=?, email=?, telephone=? WHERE id=?'
            );
            $st->execute([
                $d['nom_fr'], $d['prenom_fr'], $d['nom_ar'] ?? null, $d['prenom_ar'] ?? null,
                $d['cin'], $d['email'] ?? null, $d['telephone'] ?? null, $id,
            ]);
            return $id;
        }
        $st = $this->pdo->prepare(
            'INSERT INTO enseignants (nom_fr, prenom_fr, nom_ar, prenom_ar, cin, email, telephone) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([
            $d['nom_fr'], $d['prenom_fr'], $d['nom_ar'] ?? null, $d['prenom_ar'] ?? null,
            $d['cin'], $d['email'] ?? null, $d['telephone'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function getPersonne(string $type, int $id): ?array
    {
        $table = $type === 'etudiant' ? 'etudiants' : 'enseignants';
        $st = $this->pdo->prepare("SELECT * FROM $table WHERE id=?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }
}

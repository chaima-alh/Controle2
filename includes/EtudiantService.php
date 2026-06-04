<?php

declare(strict_types=1);

class EtudiantService
{
    public function __construct(private PDO $pdo) {}

    public function findActive(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, c.nom AS classe_nom
             FROM etudiants e
             LEFT JOIN classes c ON c.id = e.classe_id
             WHERE e.id = ? AND e.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function search(array $filters): array
    {
        $sql = 'SELECT e.*, c.nom AS classe_nom
                FROM etudiants e
                LEFT JOIN classes c ON c.id = e.classe_id
                WHERE 1=1';
        $params = [];

        if (empty($filters['include_deleted'])) {
            $sql .= ' AND e.deleted_at IS NULL';
        }

        if (!empty($filters['nom'])) {
            $sql .= ' AND (e.nom_fr LIKE ? OR e.prenom_fr LIKE ?)';
            $term = '%' . $filters['nom'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['phonetique'])) {
            $key = phoneticKey($filters['phonetique']);
            $sql .= ' AND (e.nom_fr LIKE ? OR e.prenom_fr LIKE ?)';
            $params[] = '%' . $key . '%';
            $params[] = '%' . $key . '%';
        }

        if (!empty($filters['massar'])) {
            $sql .= ' AND e.massar LIKE ?';
            $params[] = '%' . $filters['massar'] . '%';
        }

        if (!empty($filters['identifiant'])) {
            $sql .= ' AND e.identifiant LIKE ?';
            $params[] = '%' . $filters['identifiant'] . '%';
        }

        if (!empty($filters['classe_id'])) {
            $sql .= ' AND e.classe_id = ?';
            $params[] = (int) $filters['classe_id'];
        }

        $sql .= ' ORDER BY e.nom_fr, e.prenom_fr';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data, ?int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO etudiants (
                nom_fr, prenom_fr, nom_ar, prenom_ar, identifiant, cin, massar,
                email, niveau_actuel, cursus, telephone, date_naissance, classe_id, photo
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['nom_fr'], $data['prenom_fr'], $data['nom_ar'] ?? null, $data['prenom_ar'] ?? null,
            $data['identifiant'], $data['cin'] ?? null, $data['massar'],
            $data['email'] ?? null, $data['niveau_actuel'] ?? null, $data['cursus'] ?? null,
            $data['telephone'] ?? null, $data['date_naissance'] ?? null,
            $data['classe_id'] ?: null, $data['photo'] ?? 'default.png',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data, ?int $userId): void
    {
        $old = $this->findActive($id);
        if (!$old) {
            throw new RuntimeException('Étudiant introuvable.');
        }

        $fields = [
            'nom_fr', 'prenom_fr', 'nom_ar', 'prenom_ar', 'identifiant', 'cin', 'massar',
            'email', 'niveau_actuel', 'cursus', 'telephone', 'date_naissance', 'classe_id', 'photo',
        ];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $newVal = $data[$field];
            $oldVal = $old[$field] ?? null;
            if ((string) $oldVal !== (string) $newVal) {
                $this->audit($id, $userId, $field, $oldVal, $newVal);
            }
        }

        $stmt = $this->pdo->prepare(
            'UPDATE etudiants SET
                nom_fr=?, prenom_fr=?, nom_ar=?, prenom_ar=?, identifiant=?, cin=?, massar=?,
                email=?, niveau_actuel=?, cursus=?, telephone=?, date_naissance=?, classe_id=?, photo=?
             WHERE id=? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $data['nom_fr'], $data['prenom_fr'], $data['nom_ar'] ?? null, $data['prenom_ar'] ?? null,
            $data['identifiant'], $data['cin'] ?? null, $data['massar'],
            $data['email'] ?? null, $data['niveau_actuel'] ?? null, $data['cursus'] ?? null,
            $data['telephone'] ?? null, $data['date_naissance'] ?: null,
            $data['classe_id'] ?: null, $data['photo'] ?? $old['photo'],
            $id,
        ]);
    }

    public function softDelete(int $id, ?int $userId): void
    {
        $this->audit($id, $userId, 'deleted_at', null, date('Y-m-d H:i:s'));
        $stmt = $this->pdo->prepare('UPDATE etudiants SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function restore(int $id, ?int $userId): void
    {
        $this->audit($id, $userId, 'deleted_at', 'supprimé', null);
        $stmt = $this->pdo->prepare('UPDATE etudiants SET deleted_at = NULL WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getDeleted(): array
    {
        $stmt = $this->pdo->query(
            'SELECT e.*, c.nom AS classe_nom
             FROM etudiants e
             LEFT JOIN classes c ON c.id = e.classe_id
             WHERE e.deleted_at IS NOT NULL
             ORDER BY e.deleted_at DESC'
        );
        return $stmt->fetchAll();
    }

    public function getAudit(int $etudiantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.login
             FROM etudiant_audit a
             LEFT JOIN comptes c ON c.id = a.user_id
             WHERE a.etudiant_id = ?
             ORDER BY a.modifie_le DESC'
        );
        $stmt->execute([$etudiantId]);
        return $stmt->fetchAll();
    }

    private function audit(int $etudiantId, ?int $userId, string $champ, mixed $old, mixed $new): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO etudiant_audit (etudiant_id, user_id, champ, ancienne_valeur, nouvelle_valeur)
             VALUES (?,?,?,?,?)'
        );
        $stmt->execute([$etudiantId, $userId, $champ, $old, $new]);
    }

    public function listClasses(): array
    {
        return $this->pdo->query(
            'SELECT c.id, c.nom, f.alias AS filiere_alias
             FROM classes c
             JOIN filieres f ON f.id = c.filiere_id
             ORDER BY f.alias, c.nom'
        )->fetchAll();
    }

    public function exportCsv(array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="etudiants_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Identifiant', 'Massar', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Classe', 'Niveau'], ';');
        foreach ($rows as $e) {
            fputcsv($out, [
                $e['identifiant'], $e['massar'], $e['nom_fr'], $e['prenom_fr'],
                $e['email'] ?? '', $e['telephone'] ?? '', $e['classe_nom'] ?? '', $e['niveau_actuel'] ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }
}

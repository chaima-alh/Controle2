<?php

declare(strict_types=1);

class AbsenceService
{
    public function __construct(private PDO $pdo) {}

    public function ficheEtudiant(int $etudiantId, ?int $anneeId = null): array
    {
        $anneeId = $anneeId ?? anneeCouranteId($this->pdo);
        $st = $this->pdo->prepare(
            'SELECT a.*, el.titre AS element_titre, el.code AS element_code,
                    ts.libelle AS type_seance, en.nom_fr AS ens_nom, en.prenom_fr AS ens_prenom
             FROM absences a
             JOIN elements el ON el.id = a.element_id
             JOIN types_seance ts ON ts.id = a.type_seance_id
             JOIN enseignants en ON en.id = a.enseignant_id
             WHERE a.etudiant_id = ? AND a.annee_academique_id = ?
             ORDER BY a.date_heure DESC'
        );
        $st->execute([$etudiantId, $anneeId]);
        return $st->fetchAll();
    }

    public function enregistrerAbsences(
        array $etudiantIds,
        int $elementId,
        int $typeSeanceId,
        int $enseignantId,
        string $dateHeure,
        string $saisiePar = 'enseignant'
    ): int {
        $anneeId = anneeCouranteId($this->pdo);
        $st = $this->pdo->prepare(
            'INSERT INTO absences (date_heure, element_id, type_seance_id, etat, etudiant_id, enseignant_id, annee_academique_id, saisie_par)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $n = 0;
        foreach ($etudiantIds as $eid) {
            $st->execute([$dateHeure, $elementId, $typeSeanceId, 'non_justifiee', $eid, $enseignantId, $anneeId, $saisiePar]);
            $n++;
            $this->verifierAvertissement((int) $eid, $elementId, $anneeId);
        }
        return $n;
    }

    public function annuler(int $absenceId, bool $admin = false): bool
    {
        $st = $this->pdo->prepare('SELECT * FROM absences WHERE id=?');
        $st->execute([$absenceId]);
        $a = $st->fetch();
        if (!$a || $a['etat'] === 'annulee') {
            return false;
        }
        if (!$admin) {
            $seuil = (int) config($this->pdo, 'seuil_annulation_absence_jours', '7');
            $diff = (time() - strtotime($a['date_heure'])) / 86400;
            if ($diff > $seuil) {
                return false;
            }
        }
        $this->pdo->prepare('UPDATE absences SET etat="annulee" WHERE id=?')->execute([$absenceId]);
        return true;
    }

    public function updateAbsence(int $id, array $data, ?int $modifieParCompteId = null): void
    {
        // Get old values for audit
        $st = $this->pdo->prepare('SELECT * FROM absences WHERE id=?');
        $st->execute([$id]);
        $oldAbsence = $st->fetch();
        
        // Update absence
        $this->pdo->prepare(
            'UPDATE absences SET date_heure=?, element_id=?, type_seance_id=?, etat=? WHERE id=?'
        )->execute([
            $data['date_heure'], $data['element_id'], $data['type_seance_id'], $data['etat'], $id,
        ]);
        
        // Log changes to audit table
        if ($oldAbsence && $modifieParCompteId) {
            $changes = [];
            if ($oldAbsence['date_heure'] !== $data['date_heure']) {
                $changes['date_heure'] = [
                    'ancien' => $oldAbsence['date_heure'],
                    'nouveau' => $data['date_heure']
                ];
            }
            if ($oldAbsence['element_id'] != $data['element_id']) {
                $changes['element_id'] = [
                    'ancien' => $oldAbsence['element_id'],
                    'nouveau' => $data['element_id']
                ];
            }
            if ($oldAbsence['type_seance_id'] != $data['type_seance_id']) {
                $changes['type_seance_id'] = [
                    'ancien' => $oldAbsence['type_seance_id'],
                    'nouveau' => $data['type_seance_id']
                ];
            }
            if ($oldAbsence['etat'] !== $data['etat']) {
                $changes['etat'] = [
                    'ancien' => $oldAbsence['etat'],
                    'nouveau' => $data['etat']
                ];
            }
            
            if (!empty($changes)) {
                $this->auditTrailAbsence($id, $modifieParCompteId, json_encode($changes));
            }
        }
    }

    public function marquerJustifiee(int $id): void
    {
        $this->pdo->prepare('UPDATE absences SET etat="justifiee" WHERE id=?')->execute([$id]);
    }

    public function findByMassarList(string $massarList): array
    {
        $codes = array_filter(array_map('trim', preg_split('/[,;\s]+/', $massarList)));
        $found = [];
        $notFound = [];
        foreach ($codes as $code) {
            $st = $this->pdo->prepare('SELECT * FROM etudiants WHERE massar=? AND deleted_at IS NULL');
            $st->execute([$code]);
            $e = $st->fetch();
            if ($e) {
                $found[] = $e;
            } else {
                $notFound[] = $code;
            }
        }
        return ['found' => $found, 'not_found' => $notFound];
    }

    public function ajouterJustification(int $absenceId, string $fichier, bool $parEtudiant = true): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO justifications (absence_id, fichier, statut, envoye_par_etudiant) VALUES (?,?,?,?)'
        );
        $st->execute([$absenceId, $fichier, 'en_attente', (int) $parEtudiant]);
        return (int) $this->pdo->lastInsertId();
    }

    public function traiterJustification(int $id, string $statut): void
    {
        $this->pdo->prepare('UPDATE justifications SET statut=? WHERE id=?')->execute([$statut, $id]);
        if ($statut === 'acceptee') {
            $st = $this->pdo->prepare('SELECT absence_id FROM justifications WHERE id=?');
            $st->execute([$id]);
            $aid = $st->fetchColumn();
            if ($aid) {
                $this->marquerJustifiee((int) $aid);
            }
        }
    }

    public function listJustificationsEnAttente(): array
    {
        return $this->pdo->query(
            'SELECT j.*, a.date_heure, e.nom_fr, e.prenom_fr, el.titre AS element_titre
             FROM justifications j
             JOIN absences a ON a.id = j.absence_id
             JOIN etudiants e ON e.id = a.etudiant_id
             JOIN elements el ON el.id = a.element_id
             WHERE j.statut="en_attente" ORDER BY j.created_at DESC'
        )->fetchAll();
    }

    public function creerReclamation(int $absenceId, int $etudiantId, string $message): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO reclamations (absence_id, etudiant_id, message) VALUES (?,?,?)'
        );
        $st->execute([$absenceId, $etudiantId, $message]);
    }

    public function repondreReclamation(int $id, string $reponse, string $statut): void
    {
        $this->pdo->prepare('UPDATE reclamations SET reponse_admin=?, statut=? WHERE id=?')
            ->execute([$reponse, $statut, $id]);
        $st = $this->pdo->prepare('SELECT etudiant_id FROM reclamations WHERE id=?');
        $st->execute([$id]);
        $eid = (int) $st->fetchColumn();
        $notif = new NotificationService($this->pdo);
        $notif->sendToEtudiant($eid, 'Réponse à votre réclamation', $reponse);
    }

    public function reclamationsEtudiant(int $etudiantId): array
    {
        $st = $this->pdo->prepare(
            'SELECT r.*, a.date_heure, el.titre AS element_titre FROM reclamations r
             JOIN absences a ON a.id = r.absence_id
             JOIN elements el ON el.id = a.element_id
             WHERE r.etudiant_id=? ORDER BY r.created_at DESC'
        );
        $st->execute([$etudiantId]);
        return $st->fetchAll();
    }

    public function listReclamationsOuvertes(): array
    {
        return $this->pdo->query(
            'SELECT r.*, e.nom_fr, e.prenom_fr, a.date_heure, el.titre AS element_titre
             FROM reclamations r
             JOIN etudiants e ON e.id = r.etudiant_id
             JOIN absences a ON a.id = r.absence_id
             JOIN elements el ON el.id = a.element_id
             WHERE r.statut IN ("ouverte","en_cours") ORDER BY r.created_at DESC'
        )->fetchAll();
    }

    public function creerDemandePermission(int $etudiantId, int $enseignantId, ?int $elementId, string $message): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO demandes_permission (etudiant_id, enseignant_id, element_id, message) VALUES (?,?,?,?)'
        );
        $st->execute([$etudiantId, $enseignantId, $elementId, $message]);
    }

    public function traiterDemande(int $id, string $statut, string $reponse = ''): void
    {
        $this->pdo->prepare('UPDATE demandes_permission SET statut=?, reponse=?, traite_le=NOW() WHERE id=?')
            ->execute([$statut, $reponse, $id]);
        $st = $this->pdo->prepare('SELECT etudiant_id FROM demandes_permission WHERE id=?');
        $st->execute([$id]);
        $eid = (int) $st->fetchColumn();
        $msg = $statut === 'acceptee' ? 'Votre demande de permission a été acceptée.' : 'Votre demande de permission a été refusée.';
        (new NotificationService($this->pdo))->sendToEtudiant($eid, 'Demande de permission', $msg . ' ' . $reponse);
    }

    public function demandesPourEnseignant(int $enseignantId): array
    {
        $st = $this->pdo->prepare(
            'SELECT d.*, e.nom_fr, e.prenom_fr, el.titre AS element_titre
             FROM demandes_permission d
             JOIN etudiants e ON e.id = d.etudiant_id
             LEFT JOIN elements el ON el.id = d.element_id
             WHERE d.enseignant_id=? ORDER BY d.created_at DESC'
        );
        $st->execute([$enseignantId]);
        return $st->fetchAll();
    }

    public function demandesEtudiant(int $etudiantId): array
    {
        $st = $this->pdo->prepare(
            'SELECT d.*, en.nom_fr AS ens_nom, en.prenom_fr AS ens_prenom, el.titre AS element_titre
             FROM demandes_permission d
             JOIN enseignants en ON en.id = d.enseignant_id
             LEFT JOIN elements el ON el.id = d.element_id
             WHERE d.etudiant_id=? ORDER BY d.created_at DESC'
        );
        $st->execute([$etudiantId]);
        return $st->fetchAll();
    }

    public function avertissementsEtudiant(int $etudiantId): array
    {
        $st = $this->pdo->prepare(
            'SELECT av.*, el.titre AS element_titre FROM avertissements av
             JOIN elements el ON el.id = av.element_id
             WHERE av.etudiant_id=? ORDER BY av.created_at DESC'
        );
        $st->execute([$etudiantId]);
        return $st->fetchAll();
    }

    public function typesSeance(): array
    {
        return $this->pdo->query('SELECT * FROM types_seance ORDER BY libelle')->fetchAll();
    }

    public function statsParClasse(): array
    {
        return $this->pdo->query(
            'SELECT c.nom AS classe_nom, f.alias AS filiere, COUNT(e.id) AS nb_etudiants
             FROM classes c
             JOIN filieres f ON f.id = c.filiere_id
             LEFT JOIN etudiants e ON e.classe_id = c.id AND e.deleted_at IS NULL
             GROUP BY c.id ORDER BY f.alias, c.nom'
        )->fetchAll();
    }

    private function verifierAvertissement(int $etudiantId, int $elementId, int $anneeId): void
    {
        $seuil = (int) config($this->pdo, 'seuil_avertissement_absences', '3');
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM absences
             WHERE etudiant_id=? AND element_id=? AND annee_academique_id=? AND etat="non_justifiee"'
        );
        $st->execute([$etudiantId, $elementId, $anneeId]);
        $nb = (int) $st->fetchColumn();
        if ($nb < $seuil) {
            return;
        }
        $st = $this->pdo->prepare(
            'SELECT id FROM avertissements WHERE etudiant_id=? AND element_id=? AND annee_academique_id=? AND nombre_absences=?'
        );
        $st->execute([$etudiantId, $elementId, $anneeId, $nb]);
        if ($st->fetch()) {
            return;
        }
        $st = $this->pdo->prepare('SELECT titre FROM elements WHERE id=?');
        $st->execute([$elementId]);
        $titre = $st->fetchColumn();
        $msg = "Vous avez atteint $nb absences non justifiées en $titre (seuil: $seuil).";
        $this->pdo->prepare(
            'INSERT INTO avertissements (etudiant_id, element_id, annee_academique_id, nombre_absences, message)
             VALUES (?,?,?,?,?)'
        )->execute([$etudiantId, $elementId, $anneeId, $nb, $msg]);
        (new NotificationService($this->pdo))->sendToEtudiant($etudiantId, 'Avertissement absences', $msg);
    }

    private function auditTrailAbsence(int $absenceId, int $modifieParCompteId, string $changementsJson): void
    {
        try {
            $st = $this->pdo->prepare(
                'INSERT INTO audit_absences (absence_id, compte_id, changements) VALUES (?,?,?)'
            );
            $st->execute([$absenceId, $modifieParCompteId, $changementsJson]);
        } catch (Throwable $e) {
            // Audit table may not exist yet - silently fail
        }
    }
}

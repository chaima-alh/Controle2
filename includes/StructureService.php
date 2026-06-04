<?php

declare(strict_types=1);

class StructureService
{
    public function __construct(private PDO $pdo) {}

    /* Filières */
    public function listFilieres(): array
    {
        return $this->pdo->query(
            'SELECT f.*, e.nom_fr AS coord_nom, e.prenom_fr AS coord_prenom
             FROM filieres f
             LEFT JOIN enseignants e ON e.id = f.coordinateur_id
             ORDER BY f.alias'
        )->fetchAll();
    }

    public function saveFiliere(array $d, ?int $id = null): void
    {
        if ($id) {
            $st = $this->pdo->prepare(
                'UPDATE filieres SET alias=?, intitule=?, annee_accreditation_debut=?, annee_accreditation_fin=?, coordinateur_id=? WHERE id=?'
            );
            $st->execute([
                $d['alias'], $d['intitule'], $d['annee_debut'] ?: null, $d['annee_fin'] ?: null,
                $d['coordinateur_id'] ?: null, $id,
            ]);
        } else {
            $st = $this->pdo->prepare(
                'INSERT INTO filieres (alias, intitule, annee_accreditation_debut, annee_accreditation_fin, coordinateur_id)
                 VALUES (?,?,?,?,?)'
            );
            $st->execute([
                $d['alias'], $d['intitule'], $d['annee_debut'] ?: null, $d['annee_fin'] ?: null,
                $d['coordinateur_id'] ?: null,
            ]);
        }
    }

    public function deleteFiliere(int $id): void
    {
        $this->pdo->prepare('DELETE FROM filieres WHERE id=?')->execute([$id]);
    }

    /* Classes */
    public function listClasses(?int $filiereId = null): array
    {
        $sql = 'SELECT c.*, f.alias AS filiere_alias, f.intitule AS filiere_intitule
                FROM classes c JOIN filieres f ON f.id = c.filiere_id';
        if ($filiereId) {
            $sql .= ' WHERE c.filiere_id = ?';
            $st = $this->pdo->prepare($sql . ' ORDER BY c.nom');
            $st->execute([$filiereId]);
            return $st->fetchAll();
        }
        return $this->pdo->query($sql . ' ORDER BY f.alias, c.nom')->fetchAll();
    }

    public function saveClasse(array $d, ?int $id = null): void
    {
        if ($id) {
            $st = $this->pdo->prepare('UPDATE classes SET filiere_id=?, nom=?, niveau=? WHERE id=?');
            $st->execute([$d['filiere_id'], $d['nom'], $d['niveau'], $id]);
        } else {
            $st = $this->pdo->prepare('INSERT INTO classes (filiere_id, nom, niveau) VALUES (?,?,?)');
            $st->execute([$d['filiere_id'], $d['nom'], $d['niveau']]);
        }
    }

    public function deleteClasse(int $id): void
    {
        $this->pdo->prepare('DELETE FROM classes WHERE id=?')->execute([$id]);
    }

    /* Modules */
    public function listModules(): array
    {
        return $this->pdo->query('SELECT * FROM modules ORDER BY code')->fetchAll();
    }

    public function modulesByClasse(int $classeId): array
    {
        $st = $this->pdo->prepare(
            'SELECT m.* FROM modules m
             JOIN classe_module cm ON cm.module_id = m.id
             WHERE cm.classe_id = ? ORDER BY m.code'
        );
        $st->execute([$classeId]);
        return $st->fetchAll();
    }

    public function saveModule(array $d, ?int $id = null): void
    {
        if ($id) {
            $st = $this->pdo->prepare('UPDATE modules SET code=?, titre=?, niveau=? WHERE id=?');
            $st->execute([$d['code'], $d['titre'], $d['niveau'], $id]);
        } else {
            $st = $this->pdo->prepare('INSERT INTO modules (code, titre, niveau) VALUES (?,?,?)');
            $st->execute([$d['code'], $d['titre'], $d['niveau']]);
        }
    }

    public function deleteModule(int $id): void
    {
        $this->pdo->prepare('DELETE FROM modules WHERE id=?')->execute([$id]);
    }

    public function associerModuleClasse(int $classeId, int $moduleId): void
    {
        $st = $this->pdo->prepare('INSERT IGNORE INTO classe_module (classe_id, module_id) VALUES (?,?)');
        $st->execute([$classeId, $moduleId]);
    }

    public function dissocierModuleClasse(int $classeId, int $moduleId): void
    {
        $st = $this->pdo->prepare('DELETE FROM classe_module WHERE classe_id=? AND module_id=?');
        $st->execute([$classeId, $moduleId]);
    }

    /* Éléments */
    public function listElements(?int $moduleId = null): array
    {
        $sql = 'SELECT el.*, m.code AS module_code, m.titre AS module_titre
                FROM elements el JOIN modules m ON m.id = el.module_id';
        if ($moduleId) {
            $st = $this->pdo->prepare($sql . ' WHERE el.module_id=? ORDER BY el.code');
            $st->execute([$moduleId]);
            return $st->fetchAll();
        }
        return $this->pdo->query($sql . ' ORDER BY m.code, el.code')->fetchAll();
    }

    public function saveElement(array $d, ?int $id = null): void
    {
        if ($id) {
            $st = $this->pdo->prepare('UPDATE elements SET module_id=?, code=?, titre=? WHERE id=?');
            $st->execute([$d['module_id'], $d['code'], $d['titre'], $id]);
        } else {
            $st = $this->pdo->prepare('INSERT INTO elements (module_id, code, titre) VALUES (?,?,?)');
            $st->execute([$d['module_id'], $d['code'], $d['titre']]);
        }
    }

    public function deleteElement(int $id): void
    {
        $this->pdo->prepare('DELETE FROM elements WHERE id=?')->execute([$id]);
    }

    public function listEnseignants(): array
    {
        return $this->pdo->query('SELECT * FROM enseignants ORDER BY nom_fr')->fetchAll();
    }

    public function importFile(string $filepath, string $extension): array
    {
        return match (strtolower($extension)) {
            'csv' => $this->importCsv($filepath),
            'xml' => $this->importXml($filepath),
            'xlsx' => $this->importXlsx($filepath),
            default => ['ok' => false, 'message' => 'Format non supporté (csv, xml, xlsx).'],
        };
    }

    /** Import CSV : filiere_alias;filiere_intitule;classe_nom;classe_niveau;module_code;module_titre;module_niveau;element_code;element_titre */
    public function importCsv(string $filepath): array
    {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return ['ok' => false, 'message' => 'Fichier illisible'];
        }
        $count = 0;
        $header = true;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if ($header) {
                $header = false;
                if (count($row) < 5) {
                    rewind($handle);
                    $header = false;
                }
                continue;
            }
            if (count($row) < 5) {
                continue;
            }
            $this->importRow($row);
            $count++;
        }
        fclose($handle);
        return ['ok' => true, 'message' => "$count ligne(s) importée(s)"];
    }

    public function importXlsx(string $filepath): array
    {
        $rows = XlsxReader::readRows($filepath);
        $count = 0;
        $first = true;
        foreach ($rows as $row) {
            if ($first && $this->looksLikeHeader($row)) {
                $first = false;
                continue;
            }
            $first = false;
            if (count($row) < 5) {
                continue;
            }
            $this->importRow($row);
            $count++;
        }
        return ['ok' => true, 'message' => "$count ligne(s) Excel importée(s)"];
    }

    public function importXml(string $filepath): array
    {
        $xml = @simplexml_load_file($filepath);
        if (!$xml) {
            return ['ok' => false, 'message' => 'XML invalide.'];
        }
        $count = 0;
        foreach ($xml->filiere as $filiere) {
            $fAlias = (string) ($filiere['alias'] ?? $filiere->alias ?? '');
            $fIntitule = (string) ($filiere['intitule'] ?? $filiere->intitule ?? $fAlias);
            if ($fAlias === '') {
                continue;
            }
            $fId = $this->upsertFiliere($fAlias, $fIntitule);
            foreach ($filiere->classe as $classe) {
                $cNom = (string) ($classe['nom'] ?? $classe->nom ?? '');
                $cNiveau = (string) ($classe['niveau'] ?? $classe->niveau ?? 'N/A');
                if ($cNom === '') {
                    continue;
                }
                $cId = $this->upsertClasse($fId, $cNom, $cNiveau);
                foreach ($classe->module as $module) {
                    $mCode = (string) ($module['code'] ?? $module->code ?? '');
                    $mTitre = (string) ($module['titre'] ?? $module->titre ?? $mCode);
                    $mNiveau = (string) ($module['niveau'] ?? $module->niveau ?? $cNiveau);
                    if ($mCode === '') {
                        continue;
                    }
                    $mId = $this->upsertModule($mCode, $mTitre, $mNiveau);
                    $this->associerModuleClasse($cId, $mId);
                    foreach ($module->element as $element) {
                        $eCode = (string) ($element['code'] ?? $element->code ?? '');
                        $eTitre = (string) ($element['titre'] ?? $element->titre ?? $eCode);
                        if ($eCode !== '') {
                            $this->upsertElement($mId, $eCode, $eTitre);
                        }
                    }
                    $count++;
                }
            }
        }
        return ['ok' => true, 'message' => "$count module(s) importé(s) depuis XML"];
    }

    private function looksLikeHeader(array $row): bool
    {
        $first = strtolower((string) ($row[0] ?? ''));
        return str_contains($first, 'filiere') || str_contains($first, 'alias');
    }

    private function importRow(array $row): void
    {
        [$fAlias, $fIntitule, $cNom, $cNiveau, $mCode, $mTitre, $mNiveau, $eCode, $eTitre] = array_pad($row, 9, '');
        $fId = $this->upsertFiliere(trim($fAlias), trim($fIntitule));
        $cId = $this->upsertClasse($fId, trim($cNom), trim($cNiveau) ?: 'N/A');
        if ($mCode === '') {
            return;
        }
        $mId = $this->upsertModule(trim($mCode), trim($mTitre), trim($mNiveau) ?: trim($cNiveau));
        $this->associerModuleClasse($cId, $mId);
        if ($eCode !== '') {
            $this->upsertElement($mId, trim($eCode), trim($eTitre));
        }
    }

    private function upsertFiliere(string $alias, string $intitule): int
    {
        $st = $this->pdo->prepare('SELECT id FROM filieres WHERE alias=?');
        $st->execute([$alias]);
        $id = $st->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $this->pdo->prepare('INSERT INTO filieres (alias, intitule) VALUES (?,?)')->execute([$alias, $intitule ?: $alias]);
        return (int) $this->pdo->lastInsertId();
    }

    private function upsertClasse(int $filiereId, string $nom, string $niveau): int
    {
        $st = $this->pdo->prepare('SELECT id FROM classes WHERE filiere_id=? AND nom=?');
        $st->execute([$filiereId, $nom]);
        $id = $st->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $this->pdo->prepare('INSERT INTO classes (filiere_id, nom, niveau) VALUES (?,?,?)')->execute([$filiereId, $nom, $niveau]);
        return (int) $this->pdo->lastInsertId();
    }

    private function upsertModule(string $code, string $titre, string $niveau): int
    {
        $st = $this->pdo->prepare('SELECT id FROM modules WHERE code=?');
        $st->execute([$code]);
        $id = $st->fetchColumn();
        if ($id) {
            return (int) $id;
        }
        $this->pdo->prepare('INSERT INTO modules (code, titre, niveau) VALUES (?,?,?)')->execute([$code, $titre, $niveau]);
        return (int) $this->pdo->lastInsertId();
    }

    private function upsertElement(int $moduleId, string $code, string $titre): void
    {
        $st = $this->pdo->prepare('SELECT id FROM elements WHERE module_id=? AND code=?');
        $st->execute([$moduleId, $code]);
        if ($st->fetchColumn()) {
            return;
        }
        $this->pdo->prepare('INSERT INTO elements (module_id, code, titre) VALUES (?,?,?)')->execute([$moduleId, $code, $titre ?: $code]);
    }
}

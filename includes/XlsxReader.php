<?php

declare(strict_types=1);

/** Lecteur XLSX minimal (sans Composer) — première feuille uniquement */
class XlsxReader
{
    public static function readRows(string $filepath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Extension ZipArchive requise pour Excel.');
        }
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new RuntimeException('Fichier Excel invalide.');
        }

        $shared = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $sx = @simplexml_load_string($ssXml);
            if ($sx) {
                foreach ($sx->si as $si) {
                    if (isset($si->t)) {
                        $shared[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $parts = [];
                        foreach ($si->r as $r) {
                            $parts[] = (string) $r->t;
                        }
                        $shared[] = implode('', $parts);
                    } else {
                        $shared[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$sheetXml) {
            throw new RuntimeException('Feuille sheet1 introuvable.');
        }

        $sheet = @simplexml_load_string($sheetXml);
        if (!$sheet) {
            throw new RuntimeException('XML feuille illisible.');
        }

        $rows = [];
        $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                preg_match('/([A-Z]+)/', $ref, $m);
                $col = $m[1] ?? 'A';
                $colIndex = self::colToIndex($col);
                $type = (string) ($c['t'] ?? '');
                $v = (string) ($c->v ?? '');
                if ($type === 's' && isset($shared[(int) $v])) {
                    $v = $shared[(int) $v];
                }
                $cells[$colIndex] = $v;
            }
            if ($cells) {
                ksort($cells);
                $rows[] = array_values($cells);
            }
        }
        return $rows;
    }

    private static function colToIndex(string $col): int
    {
        $col = strtoupper($col);
        $n = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $n = $n * 26 + (ord($col[$i]) - 64);
        }
        return $n - 1;
    }
}

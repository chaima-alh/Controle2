<?php
$sql = file_get_contents(__DIR__ . '/migration_v2.sql');
$mysqli = new mysqli('127.0.0.1', 'root', '', 'gestion_absences');
$mysqli->multi_query($sql);
do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());
echo $mysqli->error ? 'Erreur: ' . $mysqli->error : "Migration v2 OK (remember me, IP bloquees, captcha config)\n";

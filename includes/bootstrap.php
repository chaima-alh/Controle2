<?php

declare(strict_types=1);

session_name('ensah_abs_session');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/EtudiantService.php';
require_once __DIR__ . '/StructureService.php';
require_once __DIR__ . '/CompteService.php';
require_once __DIR__ . '/AbsenceService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/Captcha.php';
require_once __DIR__ . '/SecurityService.php';
require_once __DIR__ . '/XlsxReader.php';
require_once __DIR__ . '/helpers.php';

$appConfig = require __DIR__ . '/../config/app.php';
$dbConfig  = require __DIR__ . '/../config/database.php';

$pdo = Database::getConnection($dbConfig);

Auth::loginFromRemember($pdo);

if (!is_dir($appConfig['photos_path'])) {
    mkdir($appConfig['photos_path'], 0755, true);
}

if (!is_dir($appConfig['upload_path'] . '/justifications')) {
    mkdir($appConfig['upload_path'] . '/justifications', 0755, true);
}

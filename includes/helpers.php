<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    global $appConfig;
    $base = rtrim($appConfig['base_url'] ?? '', '/');
    header('Location: ' . $base . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function config(PDO $pdo, string $cle, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT valeur FROM configuration WHERE cle = ?');
    $stmt->execute([$cle]);
    $row = $stmt->fetch();
    return $row ? (string) $row['valeur'] : $default;
}

function logNavigation(PDO $pdo, int $compteId, string $page): void
{
    $stmt = $pdo->prepare('INSERT INTO navigation_log (compte_id, page) VALUES (?, ?)');
    $stmt->execute([$compteId, $page]);
}

function generateLogin(string $nom, string $prenom, PDO $pdo): string
{
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nom . $prenom));
    $login = $base;
    $i = 1;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM comptes WHERE login = ?');
    while (true) {
        $stmt->execute([$login]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $login;
        }
        $login = $base . $i;
        $i++;
    }
}

function randomPassword(int $length = 10): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#';
    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

/** Recherche phonétique simplifiée (sons proches en français) */
function anneeCouranteId(PDO $pdo): int
{
    $id = $pdo->query('SELECT id FROM annees_academiques WHERE est_courante=1 LIMIT 1')->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $id = $pdo->query('SELECT id FROM annees_academiques ORDER BY id DESC LIMIT 1')->fetchColumn();
    return (int) $id;
}

function listAnnees(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM annees_academiques ORDER BY date_debut DESC')->fetchAll();
}

function personneIdFromUser(?array $user): ?int
{
    return isset($user['personne_id']) ? (int) $user['personne_id'] : null;
}

function photoUrl(array $appConfig, ?string $photo): string
{
    $file = $photo ?: 'default.png';
    $path = $appConfig['photos_path'] . '/' . $file;
    if (is_file($path)) {
        return $appConfig['base_url'] . '/uploads/photos/' . rawurlencode($file);
    }
    return $appConfig['base_url'] . '/assets/img/default.svg';
}

/** CSRF Token generation and management */
function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return $token !== null && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/** File upload validation with MIME type checking */
function validateUploadedFile(array $file, array $allowedMimes = []): array
{
    $errors = [];
    $mimeType = null;
    
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valid' => true, 'errors' => [], 'mime' => null];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur upload: ' . $file['error'];
        return ['valid' => false, 'errors' => $errors, 'mime' => null];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Fichier trop volumineux (max 5MB).';
    }
    
    // Check MIME type by reading file header
    if (empty($errors)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            $errors[] = 'Erreur de vérification du type fichier.';
        } else {
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedMimes)) {
                $errors[] = 'Type de fichier non autorisé. MIME: ' . ($mimeType ?: 'inconnu');
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime' => $mimeType
    ];
}

/** Password security validation */
function validatePassword(string $password): array
{
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
    }
    
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/** Generate secure password meeting requirements */
function generateSecurePassword(int $length = 12): string
{
    $password = '';
    $chars = [
        'upper' => 'ABCDEFGHJKMNPQRSTUVWXYZ',
        'lower' => 'abcdefghjkmnpqrstuvwxyz',
        'digits' => '23456789',
        'special' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
    ];
    
    // Ensure at least one from each category
    $password .= $chars['upper'][random_int(0, strlen($chars['upper']) - 1)];
    $password .= $chars['lower'][random_int(0, strlen($chars['lower']) - 1)];
    $password .= $chars['digits'][random_int(0, strlen($chars['digits']) - 1)];
    $password .= $chars['special'][random_int(0, strlen($chars['special']) - 1)];
    
    // Fill remaining with random chars
    $all = $chars['upper'] . $chars['lower'] . $chars['digits'] . $chars['special'];
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    // Shuffle
    $arr = str_split($password);
    shuffle($arr);
    return implode('', $arr);
}

function phoneticKey(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $replacements = [
        'ph' => 'f', 'qu' => 'k', 'ç' => 's', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
        'à' => 'a', 'â' => 'a', 'ù' => 'u', 'û' => 'u', 'î' => 'i', 'ï' => 'i',
        'ô' => 'o', 'œ' => 'e', 'kh' => 'k', 'gh' => 'g',
    ];
    foreach ($replacements as $from => $to) {
        $text = str_replace($from, $to, $text);
    }
    return preg_replace('/[^a-z]/', '', $text) ?? '';
}

<?php

declare(strict_types=1);

class Auth
{
    private const REMEMBER_COOKIE = 'ensah_remember';

    public static function attempt(PDO $pdo, string $login, string $password, bool $remember = false): bool
    {
        $security = new SecurityService($pdo);
        if ($security->isIpBlocked()) {
            $_SESSION['login_error'] = 'Accès refusé : adresse IP bloquée.';
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT * FROM comptes WHERE login = ? LIMIT 1'
        );
        $stmt->execute([$login]);
        $compte = $stmt->fetch();

        if (!$compte) {
            return false;
        }

        if (!(int) $compte['enabled']) {
            $_SESSION['login_error'] = 'Compte désactivé. Contactez l\'administrateur.';
            return false;
        }

        if ((int) $compte['locked']) {
            $_SESSION['login_error'] = 'Compte verrouillé après trop de tentatives.';
            return false;
        }

        if (!password_verify($password, $compte['mot_de_passe'])) {
            self::incrementFailedAttempts($pdo, (int) $compte['id']);
            return false;
        }

        self::resetFailedAttempts($pdo, (int) $compte['id']);
        self::loginSession($compte);
        self::recordConnection($pdo, (int) $compte['id']);
        if ($remember) {
            self::createRememberToken($pdo, (int) $compte['id']);
        }

        return true;
    }

    public static function loginFromRemember(PDO $pdo): bool
    {
        if (self::check() || empty($_COOKIE[self::REMEMBER_COOKIE])) {
            return false;
        }
        $security = new SecurityService($pdo);
        if ($security->isIpBlocked()) {
            return false;
        }
        $parts = explode(':', $_COOKIE[self::REMEMBER_COOKIE], 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$selector, $validator] = $parts;
        $st = $pdo->prepare(
            'SELECT t.token_hash, c.id, c.login, c.role, c.personne_type, c.personne_id
             FROM auth_remember_tokens t
             JOIN comptes c ON c.id = t.compte_id
             WHERE t.selector = ? AND t.expires_at > NOW() AND c.enabled = 1 AND c.locked = 0'
        );
        $st->execute([$selector]);
        $row = $st->fetch();
        if (!$row || !password_verify($validator, $row['token_hash'])) {
            self::clearRememberCookie();
            return false;
        }
        self::loginSession($row);
        self::recordConnection($pdo, (int) $row['id']);
        return true;
    }

    private static function createRememberToken(PDO $pdo, int $compteId): void
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $hash = password_hash($validator, PASSWORD_DEFAULT);
        $days = (int) config($pdo, 'remember_me_jours', '14');
        $expires = date('Y-m-d H:i:s', time() + $days * 86400);

        $pdo->prepare('DELETE FROM auth_remember_tokens WHERE compte_id = ?')->execute([$compteId]);
        $pdo->prepare(
            'INSERT INTO auth_remember_tokens (compte_id, selector, token_hash, expires_at) VALUES (?,?,?,?)'
        )->execute([$compteId, $selector, $hash, $expires]);

        global $appConfig;
        $path = parse_url($appConfig['base_url'] ?? '/', PHP_URL_PATH) ?: '/';
        setcookie(
            self::REMEMBER_COOKIE,
            $selector . ':' . $validator,
            [
                'expires'  => time() + $days * 86400,
                'path'     => $path,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    private static function clearRememberCookie(): void
    {
        global $appConfig;
        $path = parse_url($appConfig['base_url'] ?? '/', PHP_URL_PATH) ?: '/';
        setcookie(self::REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => $path, 'httponly' => true]);
    }

    private static function incrementFailedAttempts(PDO $pdo, int $compteId): void
    {
        $max = (int) config($pdo, 'max_tentatives_connexion', '5');

        $pdo->prepare(
            'UPDATE comptes SET tentatives_echouees = tentatives_echouees + 1 WHERE id = ?'
        )->execute([$compteId]);

        $stmt = $pdo->prepare('SELECT tentatives_echouees FROM comptes WHERE id = ?');
        $stmt->execute([$compteId]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $max) {
            $pdo->prepare('UPDATE comptes SET locked = 1 WHERE id = ?')->execute([$compteId]);
            $_SESSION['login_error'] = 'Compte verrouillé. Contactez l\'administrateur.';
        }
    }

    private static function resetFailedAttempts(PDO $pdo, int $compteId): void
    {
        $pdo->prepare(
            'UPDATE comptes SET tentatives_echouees = 0, locked = 0 WHERE id = ?'
        )->execute([$compteId]);
    }

    private static function loginSession(array $compte): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'            => (int) $compte['id'],
            'login'         => $compte['login'],
            'role'          => $compte['role'],
            'personne_type' => $compte['personne_type'],
            'personne_id'   => $compte['personne_id'],
        ];
    }

    private static function recordConnection(PDO $pdo, int $compteId): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare(
            'INSERT INTO connexions_historique (compte_id, adresse_ip) VALUES (?, ?)'
        );
        $stmt->execute([$compteId, $ip]);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login.php');
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            exit('Accès refusé.');
        }
    }

    public static function logout(): void
    {
        global $pdo;
        if (isset($pdo) && !empty($_COOKIE[self::REMEMBER_COOKIE])) {
            $parts = explode(':', $_COOKIE[self::REMEMBER_COOKIE], 2);
            if (count($parts) === 2) {
                $pdo->prepare('DELETE FROM auth_remember_tokens WHERE selector = ?')->execute([$parts[0]]);
            }
        }
        self::clearRememberCookie();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function dashboardPath(): string
    {
        return match (self::role()) {
            'administrateur' => '/admin/dashboard.php',
            'enseignant'     => '/enseignant/dashboard.php',
            'etudiant'       => '/etudiant/dashboard.php',
            default          => '/login.php',
        };
    }
}

<?php

declare(strict_types=1);

class Captcha
{
    public static function generate(): string
    {
        $a = random_int(1, 12);
        $b = random_int(1, 12);
        $_SESSION['captcha_answer'] = (string) ($a + $b);
        return "$a + $b = ?";
    }

    public static function verify(string $answer): bool
    {
        $expected = $_SESSION['captcha_answer'] ?? null;
        unset($_SESSION['captcha_answer']);
        return $expected !== null && trim($answer) === $expected;
    }

    public static function isEnabled(PDO $pdo): bool
    {
        return config($pdo, 'captcha_actif', '1') === '1';
    }
}

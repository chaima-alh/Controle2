<?php
// Simple test to confirm which file Apache is serving
// Open in browser: http://localhost/gestions%20d'abs/test.php
header('Content-Type: text/plain; charset=utf-8');
echo "FILE: " . __FILE__ . "\n";
echo "CWD: " . getcwd() . "\n";
if (function_exists('phpinfo')) {
    echo "PHP SAPI: " . PHP_SAPI . "\n";
}
?>
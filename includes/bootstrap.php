<?php
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));

if (getenv('BISAN_BASE_URL') === false && !empty($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']) ?: '';
    $root    = realpath(ROOT_DIR) ?: ROOT_DIR;
    $base    = ($docRoot !== '' && str_starts_with($root, $docRoot)) ? substr($root, strlen($docRoot)) : '';
    putenv('BISAN_BASE_URL=' . str_replace('\\', '/', $base));
}

require_once ROOT_DIR . '/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('bisan_sid');
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

<?php
const REMEMBER_COOKIE = 'bisan_remember';
const REMEMBER_DAYS = 30;

function get_current_admin(): ?array
{
    static $admin = false;
    if ($admin !== false) {
        return $admin;
    }
    $admin = null;
    if (!empty($_SESSION['admin_id'])) {
        $admin = db_fetch_one('SELECT admin_id, name, email, role FROM admin WHERE admin_id = ?', [(int)$_SESSION['admin_id']]);
    } elseif (!empty($_COOKIE[REMEMBER_COOKIE]) && is_string($_COOKIE[REMEMBER_COOKIE]) && str_contains($_COOKIE[REMEMBER_COOKIE], ':')) {
        [$adminId, $token] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
        $row = db_fetch_one('SELECT admin_id, name, email, role, remember_token FROM admin WHERE admin_id = ?', [(int)$adminId]);
        if ($row && $row['remember_token'] && hash_equals($row['remember_token'], hash('sha256', $token))) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$row['admin_id'];
            unset($row['remember_token']);
            $admin = $row;
        }
    }
    return $admin;
}

function login_admin(array $admin, bool $remember): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['admin_id'];
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        db_query('UPDATE admin SET remember_token = ? WHERE admin_id = ?', [hash('sha256', $token), $admin['admin_id']]);
        setcookie(REMEMBER_COOKIE, $admin['admin_id'] . ':' . $token, [
            'expires'  => time() + REMEMBER_DAYS * 86400,
            'path'     => BASE_URL . '/admin',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
}

function logout_admin(): void
{
    if (!empty($_SESSION['admin_id'])) {
        db_query('UPDATE admin SET remember_token = NULL WHERE admin_id = ?', [(int)$_SESSION['admin_id']]);
    }
    setcookie(REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => BASE_URL . '/admin']);
    $_SESSION = [];
    session_regenerate_id(true);
}

function require_admin_login(): array
{
    $admin = get_current_admin();
    if (!$admin) {
        send_json_error('انتهت الجلسة، سجّل الدخول من جديد.', 401);
    }
    return $admin;
}

function safe_redirect_url(?string $next): string
{
    $next = (string)$next;
    $prefix = BASE_URL . '/admin/';
    if ($next !== '' && str_starts_with($next, $prefix) && !str_contains($next, '//') && !preg_match('~[\r\n]~', $next)) {
        return $next;
    }
    return url('admin/index.html');
}

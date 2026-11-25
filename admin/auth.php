<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/auth.php';
require_once ROOT_DIR . '/includes/entities.php';

$action = (string)($_GET['action'] ?? '');
if (is_post_request()) {
    require_valid_csrf();
}
$hasAdmin = (int)db_fetch_value('SELECT COUNT(*) FROM admin') > 0;

switch ($action) {
    case 'status':
        send_json([
            'ok'        => true,
            'has_admin' => $hasAdmin,
            'logged_in' => (bool)get_current_admin(),
            'csrf'      => csrf_token(),
            'company'   => get_setting('company_name'),
            'logo'      => file_url(get_setting('logo')),
        ]);

    case 'login':
        if (!is_post_request()) send_json_error('طريقة الطلب غير مسموحة.', 405);
        $email = post_text('email', 150);
        $pass  = (string)($_POST['password'] ?? '');
        $lockedUntil = $_SESSION['login_locked_until'] ?? 0;
        $_SESSION['login_fails'] = $_SESSION['login_fails'] ?? 0;
        if ($lockedUntil > time()) {
            send_json_error('محاولات كثيرة خاطئة. حاول بعد ' . ceil(($lockedUntil - time()) / 60) . ' دقيقة.', 429);
        }
        if ($email === '' || $pass === '') {
            send_json_error('أدخل البريد الإلكتروني وكلمة المرور.', 422);
        }
        $admin = db_fetch_one('SELECT admin_id, name, email, role, password_hash FROM admin WHERE email = ?', [$email]);
        if ($admin && password_verify($pass, $admin['password_hash'])) {
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                db_query('UPDATE admin SET password_hash = ? WHERE admin_id = ?', [password_hash($pass, PASSWORD_DEFAULT), $admin['admin_id']]);
            }
            $_SESSION['login_fails'] = 0;
            unset($_SESSION['login_locked_until']);
            login_admin($admin, !empty($_POST['remember']));
            send_json(['ok' => true, 'redirect' => safe_redirect_url($_POST['next'] ?? ''), 'csrf' => csrf_token()]);
        }
        $_SESSION['login_fails']++;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 5 * 60;
            $_SESSION['login_fails'] = 0;
        }
        usleep(400000);
        send_json_error('البريد الإلكتروني أو كلمة المرور غير صحيحة.', 401);

    case 'setup':
        if (!is_post_request()) send_json_error('طريقة الطلب غير مسموحة.', 405);
        if ($hasAdmin) send_json_error('تم إنشاء حساب المشرف مسبقاً.', 403);
        $name  = post_text('name', 150);
        $email = post_text('email', 150);
        $pass  = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password2'] ?? '');
        $errors = [];
        if (mb_strlen($name) < 2) $errors['name'] = 'أدخل اسم المشرف.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'البريد الإلكتروني غير صالح.';
        if (strlen($pass) < 8) $errors['password'] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
        if ($pass !== $pass2) $errors['password2'] = 'كلمتا المرور غير متطابقتين.';
        if ($errors) send_json_error('يرجى تصحيح الحقول المحددة.', 422, $errors);
        db_query("INSERT INTO admin (name, email, password_hash, role) VALUES (?, ?, ?, 'super_admin')", [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        login_admin(db_fetch_one('SELECT admin_id, name, email, role FROM admin WHERE email = ?', [$email]), false);
        send_json(['ok' => true, 'redirect' => url('admin/index.html'), 'csrf' => csrf_token()]);

    case 'logout':
        if (!is_post_request()) send_json_error('طريقة الطلب غير مسموحة.', 405);
        logout_admin();
        send_json(['ok' => true, 'redirect' => url('admin/login.html')]);

    case 'me':
        $admin = get_current_admin();
        if (!$admin) {
            send_json(['ok' => false, 'message' => 'انتهت الجلسة، سجّل الدخول من جديد.', 'has_admin' => $hasAdmin], 401);
        }
        $counters = get_admin_counters();
        $menu = [];
        foreach (get_admin_menu() as [$group, $items]) {
            $list = [];
            foreach ($items as $item) {
                if (!admin_has_access($admin, $item[5] ?? null)) continue;
                $list[] = [
                    'key'     => $item[0],
                    'label'   => $item[1],
                    'href'    => url($item[2]),
                    'icon'    => $item[3],
                    'counter' => $item[4] ?? null,
                ];
            }
            if ($list) $menu[] = ['group' => $group, 'items' => $list];
        }
        send_json([
            'ok'       => true,
            'admin'    => ['name' => $admin['name'], 'email' => $admin['email'], 'role' => $admin['role']],
            'menu'     => $menu,
            'counters' => $counters,
            'csrf'     => csrf_token(),
            'company_short' => get_setting('company_short', 'بيسان'),
            'logo'     => file_url(get_setting('logo')),
            'site_url' => url('index.html'),
        ]);

    default:
        send_json_error('إجراء غير معروف.', 404);
}

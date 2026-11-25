<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once ROOT_DIR . '/includes/auth.php';
require_once ROOT_DIR . '/includes/entities.php';

$admin = require_admin_login();

$action = (string)($_GET['action'] ?? '');
if (is_post_request()) {
    require_valid_csrf();
}

try {
    switch ($action) {
        case 'list':     list_records(get_requested_entity());   break;
        case 'options':  list_reference_options(get_requested_entity()); break;
        case 'save':     require_post(); save_record(get_requested_entity());   break;
        case 'patch':    require_post(); update_status_field(get_requested_entity());  break;
        case 'delete':   require_post(); delete_record(get_requested_entity()); break;
        case 'settings': require_post(); save_settings(); break;
        case 'counters': send_json(['ok' => true] + get_admin_counters());
        case 'meta':      get_entity_details(get_requested_entity()); break;
        case 'dashboard': get_dashboard_data(); break;
        case 'settings_get': get_settings_values(); break;
        default: send_json_error('إجراء غير معروف.', 404);
    }
} catch (ValidationError $error) {
    if (db_connection()->inTransaction()) db_connection()->rollBack();
    send_json_error($error->getMessage(), 422, $error->errors);
} catch (PDOException $error) {
    if (db_connection()->inTransaction()) db_connection()->rollBack();
    error_log('[bisan] admin api: ' . $error->getMessage());
    $code = $error->errorInfo[1] ?? 0;
    $message = match ((int)$code) {
        1062 => 'هذه القيمة مستخدمة مسبقاً (سجل مكرر).',
        1451 => 'لا يمكن حذف هذا السجل لارتباطه بسجلات أخرى. احذف السجلات المرتبطة أولاً.',
        1452 => 'السجل المرتبط غير موجود.',
        default => 'خطأ في قاعدة البيانات.' . (APP_DEBUG ? ' ' . $error->getMessage() : ''),
    };
    send_json_error($message, 409);
} catch (RuntimeException $error) {
    if (db_connection()->inTransaction()) db_connection()->rollBack();
    send_json_error($error->getMessage(), 400);
}

function require_post(): void
{
    if (!is_post_request()) send_json_error('طريقة الطلب غير مسموحة.', 405);
}

function get_requested_entity(): array
{
    $key = (string)($_GET['e'] ?? '');
    $allEntities = get_entities();
    if (!isset($allEntities[$key])) send_json_error('كيان غير معروف.', 404);
    if (!admin_has_access(get_current_admin(), $allEntities[$key]['role'] ?? null)) send_json_error('ليس لديك صلاحية لهذا القسم.', 403);
    return ['_key' => $key] + $allEntities[$key];
}

function quote_name(string $name): string
{
    if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
        throw new RuntimeException('اسم عمود غير صالح.');
    }
    return '`' . $name . '`';
}

function primary_key_columns(array $entity): array
{
    return (array)$entity['pk'];
}

function table_columns(array $entity): array
{
    $columns = primary_key_columns($entity);
    foreach ($entity['fields'] as $fieldName => $fieldConfig) {
        if (empty($fieldConfig['virtual'])) $columns[] = $fieldName;
    }
    return array_values(array_diff(array_unique($columns), $entity['select_exclude'] ?? []));
}

function primary_key_condition(array $entity, $rawValue): array
{
    $key = is_string($rawValue) ? json_decode($rawValue, true) : null;
    if (!is_array($key)) throw new RuntimeException('مفتاح السجل مفقود.');
    $parts = [];
    $params = [];
    foreach (primary_key_columns($entity) as $column) {
        if (!isset($key[$column]) || !is_scalar($key[$column]) || $key[$column] === '') throw new RuntimeException('مفتاح السجل غير مكتمل.');
        $parts[] = 't.' . quote_name($column) . ' = ?';
        $params[] = $key[$column];
    }
    if (!empty($entity['where'])) $parts[] = '(' . $entity['where'] . ')';
    return [implode(' AND ', $parts), $params];
}

function find_existing_row(array $entity, $rawKey, bool $lock = false): array
{
    [$whereSql, $filePath] = primary_key_condition($entity, $rawKey);
    $row = db_fetch_one('SELECT t.* FROM ' . quote_name($entity['table']) . " t WHERE $whereSql" . ($lock ? ' FOR UPDATE' : ''), $filePath);
    if (!$row) throw new RuntimeException('السجل غير موجود أو تم حذفه.');
    return $row;
}

function reference_label_sql(array $reference, string $column): string
{
    [$table, $key, $label] = $reference;
    return '(SELECT ' . $label . ' FROM ' . quote_name($table) . ' WHERE ' . quote_name($table) . '.' . quote_name($key) . ' = t.' . quote_name($column) . ' LIMIT 1)';
}

function list_records(array $entity): void
{
    $select = array_map(fn($column) => 't.' . quote_name($column), table_columns($entity));
    foreach ($entity['extra_select'] ?? [] as $alias => $sql) {
        $select[] = $sql . ' AS ' . quote_name($alias);
    }
    $labelQueries = [];
    foreach ($entity['fields'] as $fieldName => $fieldConfig) {
        if ($fieldConfig['type'] === 'fk') {
            $labelQueries[$fieldName] = reference_label_sql($fieldConfig['ref'], $fieldName);
            $select[] = $labelQueries[$fieldName] . ' AS ' . quote_name($fieldName . '__label');
        }
    }

    $where = [];
    $params = [];
    if (!empty($entity['where'])) $where[] = '(' . $entity['where'] . ')';

    $allowed = array_merge(primary_key_columns($entity), table_columns($entity));
    foreach ((array)($_GET['f'] ?? []) as $column => $filterValue) {
        if (in_array($column, $allowed, true) && is_scalar($filterValue) && $filterValue !== '') {
            $where[] = 't.' . quote_name($column) . ' = ?';
            $params[] = $filterValue;
        }
    }

    $searchText = trim((string)($_GET['q'] ?? ''));
    if ($searchText !== '') {
        $searchConditions = [];
        foreach ($entity['search'] ?? [] as $column) {
            $searchConditions[] = $column . ' LIKE ?';
            $params[] = '%' . $searchText . '%';
        }
        foreach ($labelQueries as $sql) {
            $searchConditions[] = $sql . ' LIKE ?';
            $params[] = '%' . $searchText . '%';
        }
        if ($searchConditions) $where[] = '(' . implode(' OR ', $searchConditions) . ')';
    }

    foreach ($entity['fields'] as $fieldName => $fieldConfig) {
        if (!empty($fieldConfig['inline']) && isset($_GET['status_filter']) && $_GET['status_filter'] !== '') {
            $where[] = 't.' . quote_name($fieldName) . ' = ?';
            $params[] = (string)$_GET['status_filter'];
            break;
        }
    }

    $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $from = 'FROM ' . quote_name($entity['table']) . ' t';
    $total = (int)db_fetch_value("SELECT COUNT(*) $from $sqlWhere", $params);
    $perPage = 20;
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
    $offset = ($page - 1) * $perPage;
    $order = $entity['order'] ?? ('t.' . quote_name(primary_key_columns($entity)[0]) . ' DESC');

    $rows = db_fetch_all('SELECT ' . implode(', ', $select) . " $from $sqlWhere ORDER BY $order LIMIT $perPage OFFSET $offset", $params);
    foreach ($rows as &$row) {
        $keyValues = [];
        foreach (primary_key_columns($entity) as $column) $keyValues[$column] = $row[$column];
        $row['_key'] = $keyValues;
        foreach ($entity['fields'] as $fieldName => $fieldConfig) {
            if (in_array($fieldConfig['type'], ['image', 'file'], true)) {
                $row[$fieldName . '__url'] = file_url($row[$fieldName] ?? '');
            }
        }
    }
    unset($row);

    send_json(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages]);
}

function list_reference_options(array $entity): void
{
    $field = (string)($_GET['field'] ?? '');
    $fieldConfig = $entity['fields'][$field] ?? null;
    if (!$fieldConfig || $fieldConfig['type'] !== 'fk') send_json_error('حقل غير صالح.', 404);
    [$table, $key, $label] = $fieldConfig['ref'];
    $where = !empty($fieldConfig['ref_where']) ? 'WHERE ' . $fieldConfig['ref_where'] : '';
    $rows = db_fetch_all('SELECT ' . quote_name($key) . ' AS value, ' . $label . ' AS label FROM ' . quote_name($table) . " $where ORDER BY " . quote_name($key) . ' DESC LIMIT 1000');
    send_json(['ok' => true, 'options' => $rows]);
}

function read_field_value(string $name, array $fieldConfig, ?array $existingRow, array &$errors, array &$newFiles, array &$oldFiles)
{
    $label = $fieldConfig['label'];
    $type = $fieldConfig['type'];

    if ($type === 'bool') {
        return (($_POST[$name] ?? '0') === '1') ? 1 : 0;
    }

    if ($type === 'image' || $type === 'file') {
        $uploaded = save_uploaded_file($name, $fieldConfig['folder'] ?? 'misc', $fieldConfig['ext'] ?? ['jpg', 'jpeg', 'png', 'webp']);
        if ($uploaded !== null) {
            $newFiles[] = $uploaded;
            if ($existingRow && !empty($existingRow[$name])) $oldFiles[] = $existingRow[$name];
            return $uploaded;
        }
        if (($_POST['_remove_' . $name] ?? '') === '1') {
            if ($existingRow && !empty($existingRow[$name])) $oldFiles[] = $existingRow[$name];
            if (!empty($fieldConfig['required'])) $errors[$name] = 'يرجى رفع ' . $label . '.';
            return null;
        }
        $keep = $existingRow[$name] ?? null;
        if (!empty($fieldConfig['required']) && empty($keep)) $errors[$name] = 'يرجى رفع ' . $label . '.';
        return $keep;
    }

    $rawValue = $_POST[$name] ?? '';
    if (!is_string($rawValue)) $rawValue = '';
    $value = trim($rawValue);
    if ($value === '') {
        if (!empty($fieldConfig['required'])) $errors[$name] = $label . ' مطلوب.';
        return null;
    }

    switch ($type) {
        case 'email':
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[$name] = 'البريد الإلكتروني غير صالح.';
            return mb_substr($value, 0, 150);
        case 'tel':
            if (!is_valid_phone($value)) $errors[$name] = 'رقم الهاتف غير صالح.';
            return $value;
        case 'number':
            if (!preg_match('/^-?\d+$/', $value)) { $errors[$name] = 'أدخل رقماً صحيحاً.'; return null; }
            $number = (int)$value;
            if (isset($fieldConfig['min']) && $number < $fieldConfig['min']) $errors[$name] = 'القيمة يجب ألا تقل عن ' . $fieldConfig['min'] . '.';
            if (isset($fieldConfig['max']) && $number > $fieldConfig['max']) $errors[$name] = 'القيمة يجب ألا تزيد عن ' . $fieldConfig['max'] . '.';
            return $number;
        case 'money':
            $value = str_replace(',', '', $value);
            if (!is_numeric($value)) { $errors[$name] = 'أدخل مبلغاً صحيحاً.'; return null; }
            if ((float)$value < ($fieldConfig['min'] ?? 0)) $errors[$name] = 'المبلغ لا يمكن أن يكون سالباً.';
            return round((float)$value, 2);
        case 'date':
            if (!is_valid_date($value)) $errors[$name] = 'التاريخ غير صالح.';
            return $value;
        case 'time':
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)) $errors[$name] = 'الوقت غير صالح.';
            return strlen($value) === 5 ? $value . ':00' : $value;
        case 'enum':
            $allowedValues = array_diff(array_map('strval', array_keys(enum_labels($fieldConfig['labels']))), $fieldConfig['exclude'] ?? []);
            if (!in_array($value, $allowedValues, true)) $errors[$name] = 'قيمة غير مسموحة.';
            return $value;
        case 'fk':
            [$table, $key] = $fieldConfig['ref'];
            $whereSql = !empty($fieldConfig['ref_where']) ? ' AND (' . $fieldConfig['ref_where'] . ')' : '';
            if (!db_fetch_value('SELECT 1 FROM ' . quote_name($table) . ' WHERE ' . quote_name($key) . " = ?$whereSql", [$value])) $errors[$name] = 'العنصر المختار غير موجود.';
            return $value;
        case 'icon':
            if (!preg_match('/^[a-z0-9_]{2,40}$/', $value)) $errors[$name] = 'اسم الأيقونة غير صالح (أحرف إنجليزية صغيرة و _).';
            return $value;
        case 'textarea':
            return mb_substr($value, 0, $fieldConfig['maxlength'] ?? 20000);
        default:
            return mb_substr($value, 0, $fieldConfig['maxlength'] ?? 255);
    }
}

function save_record(array $entity): void
{
    $pdo = db_connection();
    $isUpdate = !empty($_POST['_key']);
    if (!$isUpdate && !($entity['can_add'] ?? true)) throw new RuntimeException('الإضافة غير متاحة لهذا القسم.');

    $newFiles = [];
    $oldFiles = [];
    $pdo->beginTransaction();
    try {
        $existingRow = $isUpdate ? find_existing_row($entity, $_POST['_key'], true) : null;
        $errors = [];
        $data = [];
        foreach ($entity['fields'] as $name => $fieldConfig) {
            if (($fieldConfig['form'] ?? true) === false || !empty($fieldConfig['virtual']) || !empty($fieldConfig['readonly'])) continue;
            $data[$name] = read_field_value($name, $fieldConfig, $existingRow, $errors, $newFiles, $oldFiles);
        }
        if (!$isUpdate) {
            foreach ($entity['defaults'] ?? [] as $column => $fieldValue) $data[$column] = $fieldValue;
        }
        if ($errors) throw new ValidationError($errors);
        if (isset($entity['before_save'])) $data = $entity['before_save']($data, $existingRow);

        if ($isUpdate) {
            $assignments = [];
            $params = [];
            foreach ($data as $column => $fieldValue) { $assignments[] = quote_name($column) . ' = ?'; $params[] = $fieldValue; }
            [$whereSql, $whereParams] = primary_key_condition($entity, $_POST['_key']);
            db_query('UPDATE ' . quote_name($entity['table']) . ' t SET ' . implode(', ', array_map(fn($assignment) => 't.' . $assignment, $assignments)) . " WHERE $whereSql", array_merge($params, $whereParams));
            $key = [];
            foreach (primary_key_columns($entity) as $column) $key[$column] = $data[$column] ?? $existingRow[$column];
        } else {
            $columns = array_keys($data);
            db_query('INSERT INTO ' . quote_name($entity['table']) . ' (' . implode(', ', array_map('quote_name', $columns)) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            $key = [];
            foreach (primary_key_columns($entity) as $column) $key[$column] = $data[$column] ?? $pdo->lastInsertId();
        }

        if (isset($entity['after_save'])) $entity['after_save']($data, $existingRow);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($newFiles as $filePath) delete_uploaded_file($filePath);
        throw $error;
    }
    foreach ($oldFiles as $filePath) delete_uploaded_file($filePath);

    send_json(['ok' => true, 'message' => $isUpdate ? 'تم حفظ التعديلات.' : 'تمت إضافة ' . $entity['singular'] . ' بنجاح.', 'key' => $key]);
}

function update_status_field(array $entity): void
{
    $field = (string)($_POST['field'] ?? '');
    $fieldConfig = $entity['fields'][$field] ?? null;
    if (!$fieldConfig || empty($fieldConfig['inline'])) throw new RuntimeException('لا يمكن تعديل هذا الحقل مباشرة.');
    $value = (string)($_POST['value'] ?? '');
    $allowedValues = array_diff(array_map('strval', array_keys(enum_labels($fieldConfig['labels']))), $fieldConfig['exclude'] ?? []);
    if (!in_array($value, $allowedValues, true)) throw new RuntimeException('قيمة غير مسموحة.');
    find_existing_row($entity, $_POST['_key'] ?? '');
    [$whereSql, $whereParams] = primary_key_condition($entity, $_POST['_key']);
    db_query('UPDATE ' . quote_name($entity['table']) . ' t SET t.' . quote_name($field) . " = ? WHERE $whereSql", array_merge([$value], $whereParams));
    send_json(['ok' => true, 'message' => 'تم تحديث ' . $fieldConfig['label'] . ' إلى: ' . enum_label_text($fieldConfig['labels'], $value), 'counters' => get_admin_counters()]);
}

function delete_record(array $entity): void
{
    if (!($entity['can_delete'] ?? true)) throw new RuntimeException('الحذف غير متاح لهذا القسم.');
    $pdo = db_connection();
    $pdo->beginTransaction();
    $existingRow = find_existing_row($entity, $_POST['_key'] ?? '', true);
    if (isset($entity['before_delete'])) $entity['before_delete']($existingRow);
    [$whereSql, $whereParams] = primary_key_condition($entity, $_POST['_key']);
    db_query('DELETE t FROM ' . quote_name($entity['table']) . " t WHERE $whereSql", $whereParams);
    if (isset($entity['after_delete'])) $entity['after_delete']($existingRow);
    $pdo->commit();
    foreach ($entity['fields'] as $fieldName => $fieldConfig) {
        if (in_array($fieldConfig['type'], ['image', 'file'], true)) delete_uploaded_file($existingRow[$fieldName] ?? null);
    }
    send_json(['ok' => true, 'message' => 'تم حذف ' . $entity['singular'] . '.', 'counters' => get_admin_counters()]);
}

function get_settings_schema(): array
{
    return require ROOT_DIR . '/includes/settings_schema.php';
}

function save_settings(): void
{
    $schema = get_settings_schema();
    $errors = [];
    $values = [];
    $newFiles = [];
    $oldFiles = [];
    $current = [];
    foreach ($schema as $group) foreach ($group['fields'] as $key => $fieldConfig) $current[$key] = get_setting($key);

    foreach ($schema as $group) {
        foreach ($group['fields'] as $key => $fieldConfig) {
            if ($fieldConfig['type'] === 'image') {
                $uploadedPath = save_uploaded_file($key, 'branding', ['jpg', 'jpeg', 'png', 'webp']);
                if ($uploadedPath !== null) {
                    $newFiles[] = $uploadedPath;
                    $oldFiles[] = $current[$key] ?? '';
                    $values[$key] = $uploadedPath;
                }
                continue;
            }
            $value = post_text($key, $fieldConfig['type'] === 'textarea' ? 2000 : 500);
            if (!empty($fieldConfig['required']) && $value === '') $errors[$key] = $fieldConfig['label'] . ' مطلوب.';
            if ($value !== '' && $fieldConfig['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[$key] = 'البريد الإلكتروني غير صالح.';
            if ($value !== '' && $fieldConfig['type'] === 'tel' && !is_valid_phone($value)) $errors[$key] = 'رقم الهاتف غير صالح.';
            if ($value !== '' && $fieldConfig['type'] === 'url' && !preg_match('~^(https?://|#)~', $value)) $errors[$key] = 'الرابط يجب أن يبدأ بـ https://';
            $values[$key] = $value;
        }
    }
    if ($errors) {
        foreach ($newFiles as $filePath) delete_uploaded_file($filePath);
        throw new ValidationError($errors);
    }
    $pdo = db_connection();
    $pdo->beginTransaction();
    $company = [];
    foreach (COMPANY_KEYS as $settingKey => $column) {
        $company[$column] = array_key_exists($settingKey, $values) ? $values[$settingKey] : ($current[$settingKey] ?? '');
        unset($values[$settingKey]);
    }
    db_query('INSERT INTO company (company_id, name, description, logo, phone, email, address) VALUES (1, ?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), logo = VALUES(logo),
                               phone = VALUES(phone), email = VALUES(email), address = VALUES(address)',
        [$company['name'], $company['description'] ?: null, $company['logo'] ?: null, $company['phone'], $company['email'], $company['address']]);
    foreach ($values as $settingKey => $value) {
        db_query('INSERT INTO setting (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$settingKey, $value]);
    }
    $pdo->commit();
    $values += ['logo' => $company['logo']];
    foreach ($oldFiles as $filePath) delete_uploaded_file($filePath);
    $files = [];
    foreach ($newFiles as $filePath) {
        $settingKey = array_search($filePath, $values, true);
        if ($settingKey !== false) $files[$settingKey] = file_url($filePath);
    }
    send_json(['ok' => true, 'message' => 'تم حفظ بيانات الشركة.', 'files' => (object)$files]);
}

function get_entity_details(array $entity): void
{
    $key = $entity['_key'];
    $meta = get_entity_info($key);
    $filters = [];
    foreach ((array)($_GET['f'] ?? []) as $column => $filterValue) {
        if (!is_scalar($filterValue) || $filterValue === '' || (!isset($entity['fields'][$column]) && !in_array($column, (array)$entity['pk'], true))) {
            continue;
        }
        $fieldConfig = $entity['fields'][$column] ?? ['label' => $column, 'type' => 'number'];
        $text = (string)$filterValue;
        if (($fieldConfig['type'] ?? '') === 'fk') {
            [$refTable, $refKey, $refLabel] = $fieldConfig['ref'];
            $text = (string)(db_fetch_value("SELECT $refLabel FROM " . quote_name($refTable) . ' WHERE ' . quote_name($refKey) . ' = ?', [$filterValue]) ?? $filterValue);
        } elseif ($column === 'request_id') {
            $text = format_request_code((int)$filterValue);
        }
        $filters[$column] = ['value' => (string)$filterValue, 'label' => $fieldConfig['label'] === '#' ? 'رقم' : $fieldConfig['label'], 'text' => $text];
    }
    $meta['filters'] = (object)$filters;
    $meta['active_menu'] = $key === 'visit_parts' ? 'visits' : $key;
    send_json(['ok' => true, 'entity' => $meta]);
}

function get_dashboard_data(): void
{
    $stats = [
        ['label' => 'إجمالي المنتجات', 'value' => number_format((int)db_fetch_value('SELECT COUNT(*) FROM product')), 'icon' => 'inventory_2', 'tone' => '', 'href' => 'manage.html?e=products'],
        ['label' => 'طلبات الصيانة الجديدة', 'value' => number_format((int)db_fetch_value("SELECT COUNT(*) FROM service_request WHERE status = 'new'")), 'icon' => 'build_circle', 'tone' => 'icon-tile-warning', 'href' => 'manage.html?e=requests&status_filter=new'],
        ['label' => 'رسائل غير مقروءة', 'value' => number_format((int)db_fetch_value("SELECT COUNT(*) FROM contact_message WHERE status = 'unread'")), 'icon' => 'mark_email_unread', 'tone' => 'icon-tile-error', 'href' => 'manage.html?e=messages&status_filter=unread'],
        ['label' => 'العملاء المسجلون', 'value' => number_format((int)db_fetch_value('SELECT COUNT(*) FROM customer')), 'icon' => 'group', 'tone' => 'icon-tile-green', 'href' => 'manage.html?e=customers'],
    ];
    $stats2 = [
        ['label' => 'مشاريع قيد التنفيذ', 'value' => number_format((int)db_fetch_value("SELECT COUNT(*) FROM project WHERE status IN ('planning','in_progress')")), 'icon' => 'apartment', 'href' => 'manage.html?e=projects'],
        ['label' => 'زيارات اليوم', 'value' => number_format((int)db_fetch_value('SELECT COUNT(*) FROM visit WHERE scheduled_date = CURDATE()')), 'icon' => 'event', 'href' => 'manage.html?e=visits'],
        ['label' => 'فواتير غير مدفوعة', 'value' => format_price(db_fetch_value("SELECT COALESCE(SUM(amount),0) FROM invoice WHERE status IN ('unpaid','partial')")), 'icon' => 'receipt_long', 'href' => 'manage.html?e=invoices'],
        ['label' => 'قطع منخفضة المخزون', 'value' => number_format((int)db_fetch_value('SELECT COUNT(*) FROM spare_part WHERE stock_quantity < 5')), 'icon' => 'warning', 'href' => 'manage.html?e=parts'],
    ];

    $requests = array_map(fn($row) => [
        'id'       => (int)$row['request_id'],
        'code'     => format_request_code((int)$row['request_id']),
        'customer' => $row['customer'],
        'service'  => $row['service_name'],
        'address'  => $row['address'],
        'preferred_date' => $row['preferred_date'],
        'priority' => ['text' => enum_label_text('priority', $row['priority']), 'tone' => enum_labels('priority')[$row['priority']][1] ?? 'neutral'],
        'status'   => $row['status'],
    ], db_fetch_all("SELECT r.request_id, r.status, r.priority, r.preferred_date, c.name AS customer,
                     COALESCE(r.address, c.address) AS address, s.service_name
              FROM service_request r
              JOIN customer c ON c.customer_id = r.customer_id
              JOIN service s ON s.service_id = r.service_id
              ORDER BY r.date_created DESC LIMIT 8"));

    $messages = array_map(fn($message) => [
        'id'      => (int)$message['message_id'],
        'subject' => $message['subject'],
        'sender'  => $message['sender'],
        'date'    => substr($message['date_created'], 0, 10),
        'status'  => ['text' => enum_label_text('message_status', $message['status']), 'tone' => enum_labels('message_status')[$message['status']][1] ?? 'neutral'],
    ], db_fetch_all('SELECT m.message_id, m.subject, m.status, m.date_created, c.name AS sender
              FROM contact_message m JOIN customer c ON c.customer_id = m.customer_id
              ORDER BY m.date_created DESC LIMIT 5'));

    $visits = array_map(fn($visit) => [
        'when'     => $visit['scheduled_date'] . ($visit['arrival_time'] ? ' ' . substr($visit['arrival_time'], 0, 5) : ''),
        'customer' => $visit['customer'],
        'tech'     => $visit['tech'],
    ], db_fetch_all('SELECT v.scheduled_date, v.arrival_time, t.name AS tech, c.name AS customer
              FROM visit v
              JOIN service_request r ON r.request_id = v.request_id
              JOIN customer c ON c.customer_id = r.customer_id
              JOIN technician t ON t.technician_id = v.technician_id
              WHERE v.scheduled_date >= CURDATE() ORDER BY v.scheduled_date, v.arrival_time LIMIT 5'));

    $statusOptions = [];
    foreach (enum_labels('request_status') as $value => [$text, $tone]) {
        $statusOptions[] = ['value' => $value, 'text' => $text, 'tone' => $tone];
    }

    send_json(['ok' => true, 'stats' => $stats, 'stats2' => $stats2, 'requests' => $requests,
              'messages' => $messages, 'visits' => $visits, 'request_status' => $statusOptions]);
}

function get_settings_values(): void
{
    $groups = [];
    foreach (get_settings_schema() as $group) {
        $fields = [];
        foreach ($group['fields'] as $key => $fieldConfig) {
            $fieldValue = get_setting($key);
            $fields[] = $fieldConfig + [
                'key'   => $key,
                'value' => $fieldValue,
                'url'   => $fieldConfig['type'] === 'image' ? file_url($fieldValue, url('assets/img/placeholder.svg')) : null,
            ];
        }
        $groups[] = ['title' => $group['title'], 'icon' => $group['icon'], 'fields' => $fields];
    }
    send_json(['ok' => true, 'groups' => $groups]);
}

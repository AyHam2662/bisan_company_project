<?php
function db_connection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $connectionString = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($connectionString, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $error) {
            http_response_code(500);
            $message = 'تعذّر الاتصال بقاعدة البيانات. تحقق من config/config.php';
            if (APP_DEBUG) {
                $message .= ' — ' . $error->getMessage();
            }
            if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
            } else {
                echo '<!doctype html><meta charset="utf-8"><p dir="rtl" style="font-family:sans-serif;padding:2rem">' . htmlspecialchars($message) . '</p>';
            }
            exit;
        }
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $statement = db_connection()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function db_fetch_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_fetch_one(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_fetch_value(string $sql, array $params = [])
{
    $value = db_query($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

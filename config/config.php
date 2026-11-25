<?php
define('DB_HOST', getenv('BISAN_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('BISAN_DB_PORT') ?: '3306');
define('DB_NAME', getenv('BISAN_DB_NAME') ?: 'bisan_db');
define('DB_USER', getenv('BISAN_DB_USER') ?: 'bisan');
define('DB_PASS', getenv('BISAN_DB_PASS') !== false ? getenv('BISAN_DB_PASS') : 'Bisan#Db2026');

define('BASE_URL', rtrim(getenv('BISAN_BASE_URL') ?: '', '/'));

define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('MAX_UPLOAD_MB', 8);

define('APP_DEBUG', getenv('BISAN_DEBUG') === '1');

date_default_timezone_set('Asia/Hebron');

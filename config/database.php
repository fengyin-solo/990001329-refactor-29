<?php
// 数据库配置：默认值与线上运行时行为保持一致；
// 构建、流水线、部署环境可通过同名环境变量覆盖，无需修改本文件。
if (!function_exists('dbConfigEnv')) {
    function dbConfigEnv($key, $default) {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

define('DB_HOST', dbConfigEnv('DB_HOST', 'localhost'));
define('DB_USER', dbConfigEnv('DB_USER', 'root'));
define('DB_PASS', dbConfigEnv('DB_PASS', '123456'));
define('DB_NAME', dbConfigEnv('DB_NAME', 'community_board'));
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['code' => 500, 'msg' => '数据库连接失败']));
        }
    }
    return $pdo;
}

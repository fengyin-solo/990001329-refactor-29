<?php
/**
 * 数据库结构迁移入口。
 *
 * 新安装、旧库升级和流水线部署统一执行 database/migrations 下的有序 SQL。
 * 已执行的迁移只记录一次；迁移本身保持幂等，重复执行不会破坏业务数据。
 */

function loadDatabaseConfig() {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $configFile = __DIR__ . '/../config/database.php';
    if (is_file($configFile)) {
        require_once $configFile;
    }

    $config = [
        'host' => getenv('DB_HOST') !== false ? getenv('DB_HOST') : (defined('DB_HOST') ? DB_HOST : 'localhost'),
        'user' => getenv('DB_USER') !== false ? getenv('DB_USER') : (defined('DB_USER') ? DB_USER : 'root'),
        'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : (defined('DB_PASS') ? DB_PASS : '123456'),
        'name' => getenv('DB_NAME') !== false ? getenv('DB_NAME') : (defined('DB_NAME') ? DB_NAME : 'community_board'),
        'charset' => getenv('DB_CHARSET') !== false ? getenv('DB_CHARSET') : (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'),
    ];

    return $config;
}

function connectDatabase(array $config, $withDatabase = true) {
    $dsn = 'mysql:host=' . $config['host'] . ($withDatabase ? ';dbname=' . $config['name'] : '') . ';charset=' . $config['charset'];
    return new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function quoteIdentifier($identifier) {
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function splitSqlStatements($sql) {
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $buffer .= $char;

        if ($char === '\\' && ($inSingleQuote || $inDoubleQuote) && isset($sql[$i + 1])) {
            $buffer .= $sql[++$i];
            continue;
        }

        if (!$inDoubleQuote && !$inBacktick && $char === "'") {
            $inSingleQuote = !$inSingleQuote;
        } elseif (!$inSingleQuote && !$inBacktick && $char === '"') {
            $inDoubleQuote = !$inDoubleQuote;
        } elseif (!$inSingleQuote && !$inDoubleQuote && $char === '`') {
            $inBacktick = !$inBacktick;
        }

        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            $statement = trim(substr($buffer, 0, -1));
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function acquireDatabaseLock(PDO $db, $name, $timeout = 30) {
    $stmt = $db->prepare('SELECT GET_LOCK(?, ?)');
    $stmt->execute([$name, $timeout]);
    if (!(int) $stmt->fetchColumn()) {
        throw new RuntimeException('无法获取数据库锁，可能有其他部署任务正在执行: ' . $name);
    }
}

function releaseDatabaseLock(PDO $db, $name) {
    $stmt = $db->prepare('SELECT RELEASE_LOCK(?)');
    $stmt->execute([$name]);
}

function createMigrationTable(PDO $db) {
    $db->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` VARCHAR(190) NOT NULL PRIMARY KEY,
        `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结构迁移记录'");
}

function executedMigrations(PDO $db) {
    $stmt = $db->query("SELECT version FROM schema_migrations");
    return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
}

function migrateDatabase(array $config = null) {
    if ($config === null) {
        $config = loadDatabaseConfig();
    }

    $rootPdo = connectDatabase($config, false);
    $databaseName = quoteIdentifier($config['name']);
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS {$databaseName} DEFAULT CHARACTER SET {$config['charset']} COLLATE utf8mb4_unicode_ci");
    $rootPdo->exec("USE {$databaseName}");

    $lockName = $config['name'] . '_migrate';
    acquireDatabaseLock($rootPdo, $lockName);

    try {
        createMigrationTable($rootPdo);
        $executed = executedMigrations($rootPdo);

        $files = glob(__DIR__ . '/migrations/*.sql');
        sort($files, SORT_STRING);

        $applied = [];
        $insert = $rootPdo->prepare("INSERT INTO schema_migrations (version) VALUES (?)");

        foreach ($files as $file) {
            $version = basename($file, '.sql');
            if (isset($executed[$version])) {
                continue;
            }

            $statements = splitSqlStatements(file_get_contents($file));
            foreach ($statements as $statement) {
                if (preg_match('/^USE\s+/i', $statement)) {
                    continue;
                }
                $rootPdo->exec($statement);
            }

            $insert->execute([$version]);
            $applied[] = $version;
        }

        releaseDatabaseLock($rootPdo, $lockName);
        return ['pdo' => $rootPdo, 'applied' => $applied];
    } catch (Exception $e) {
        releaseDatabaseLock($rootPdo, $lockName);
        throw $e;
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        $result = migrateDatabase();
        if ($result['applied']) {
            echo "已执行迁移: " . implode(', ', $result['applied']) . "\n";
        } else {
            echo "数据库结构已是最新。\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "数据库迁移失败: " . $e->getMessage() . "\n");
        exit(1);
    }
}

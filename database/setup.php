<?php
/**
 * 统一安装入口。
 *
 * 编排顺序：结构迁移 -> 默认管理员 -> 可选示例数据。
 * Web 安装默认带示例数据；生产/流水线通常使用 cli_install.php 的结构模式。
 */

require_once __DIR__ . '/migrate.php';
require_once __DIR__ . '/seed_admin.php';
require_once __DIR__ . '/seed_samples.php';

function runDatabaseSetup(array $options = []) {
    $withSamples = !empty($options['with_samples']);

    $migration = migrateDatabase();
    $db = $migration['pdo'];
    $admin = seedDefaultAdmin($db);
    $samples = ['inserted' => false, 'count' => 0];

    if ($withSamples) {
        $samples = seedSampleMessages($db);
    }

    return [
        'migrations' => $migration['applied'],
        'admin' => $admin,
        'samples' => $samples,
    ];
}

function setupTextResult(array $result) {
    $lines = [];
    if ($result['migrations']) {
        $lines[] = '已执行结构迁移: ' . implode(', ', $result['migrations']);
    } else {
        $lines[] = '数据库结构已是最新。';
    }

    if ($result['admin']['created']) {
        $lines[] = '默认管理员已创建: ' . $result['admin']['username'];
    } else {
        $lines[] = '默认管理员已存在，保留现有账号和密码。';
    }

    if ($result['samples']['inserted']) {
        $lines[] = '示例留言已导入: ' . $result['samples']['count'] . ' 条。';
    } elseif (!$withSamples) {
        $lines[] = '示例留言未导入：当前为仅结构初始化。';
    } else {
        $lines[] = '示例留言未导入：留言表已有数据。';
    }

    return $lines;
}

function ensureUploadDirectory() {
    $directory = __DIR__ . '/../uploads';
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    return $directory;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(403);
        echo "请通过项目根目录的 install.php 或 cli_install.php 执行安装。\n";
        exit;
    }

    $withSamples = in_array('--with-samples', $argv ?? [], true);

    try {
        $result = runDatabaseSetup(['with_samples' => $withSamples]);
        ensureUploadDirectory();
        foreach (setupTextResult($result) as $line) {
            echo $line . "\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "数据库安装失败: " . $e->getMessage() . "\n");
        exit(1);
    }
}

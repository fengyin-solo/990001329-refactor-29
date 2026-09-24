<?php
/**
 * 命令行数据库初始化入口。
 *
 * 用法:
 *   php cli_install.php                 # 创建/升级表结构并确保默认管理员
 *   php cli_install.php --with-samples  # 仅空库时导入示例留言
 */

require_once __DIR__ . '/database/setup.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("请使用 php cli_install.php 执行命令行安装。\n");
}

$withSamples = in_array('--with-samples', $argv ?? [], true);

if (in_array('--help', $argv ?? [], true) || in_array('-h', $argv ?? [], true)) {
    echo "用法:\n";
    echo "  php cli_install.php                 创建/升级表结构并确保默认管理员\n";
    echo "  php cli_install.php --with-samples  仅空库时导入示例留言\n";
    exit(0);
}

try {
    $result = runDatabaseSetup(['with_samples' => $withSamples]);
    ensureUploadDirectory();
    foreach (setupTextResult($result) as $line) {
        echo $line . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "安装失败: " . $e->getMessage() . "\n");
    exit(1);
}

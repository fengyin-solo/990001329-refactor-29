<?php
/**
 * 命令行安装/迁移脚本 —— 构建、流水线、部署、本地开发的统一入口
 *
 * 用法: php cli_install.php
 *
 * 与 Web 安装（install.php）共用 includes/migration.php，幂等可重复执行：
 * 新安装、旧库升级、重复执行都会得到相同的表结构、默认管理员和示例数据，
 * 不影响已有的留言、收藏、举报数据及管理员账号。
 */
require_once __DIR__ . '/includes/migration.php';

try {
    foreach (runInstall() as $line) {
        echo "[OK] $line\n";
    }
    echo "安装/迁移完成。\n";
    exit(0);
} catch (Exception $e) {
    fwrite(STDERR, "安装失败: " . $e->getMessage() . "\n");
    exit(1);
}

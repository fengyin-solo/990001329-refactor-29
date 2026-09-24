<?php
/**
 * Web 安装脚本 - 与 cli_install.php 共用同一套安装/迁移逻辑
 *
 * 幂等可重复执行：新安装、旧库升级、重复访问结果一致，
 * 不影响已有数据和管理员账号。安装完成后建议删除本文件。
 */
require_once __DIR__ . '/includes/migration.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $logs = runInstall();
} catch (Exception $e) {
    die("安装失败: " . $e->getMessage());
}

echo "<h2>安装成功！</h2>";
echo "<ul>";
foreach ($logs as $line) {
    echo "<li>" . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "</li>";
}
echo "</ul>";
echo "<p>数据库和表已创建完成。</p>";
echo "<p>后台管理账号：<strong>admin</strong> / <strong>admin123</strong>（已存在的账号保持不变）</p>";
echo "<p><a href='index.php'>访问首页</a> | <a href='admin/login.php'>进入后台</a></p>";
echo "<p style='color:red;'>请删除此安装文件 (install.php) 以确保安全！</p>";

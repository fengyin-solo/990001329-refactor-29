<?php
/**
 * Web 数据库初始化入口。
 * 可重复执行：不会重建表，也不会覆盖已有留言、收藏、举报或管理员密码。
 */

require_once __DIR__ . '/database/setup.php';

try {
    $result = runDatabaseSetup(['with_samples' => true]);
    ensureUploadDirectory();
    $resultLines = setupTextResult($result);

    echo "<h2>安装成功！</h2>";
    foreach ($resultLines as $line) {
        echo "<p>" . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "</p>";
    }
    if ($result['admin']['created']) {
        echo "<p>后台默认管理员：<strong>" . htmlspecialchars(DEFAULT_ADMIN_USERNAME, ENT_QUOTES, 'UTF-8') . "</strong> / <strong>" . htmlspecialchars(DEFAULT_ADMIN_PASSWORD, ENT_QUOTES, 'UTF-8') . "</strong></p>";
    } else {
        echo "<p>后台管理员 <strong>" . htmlspecialchars(DEFAULT_ADMIN_USERNAME, ENT_QUOTES, 'UTF-8') . "</strong> 已存在，请继续使用当前密码登录。</p>";
    }
    echo "<p><a href='index.php'>访问首页</a> | <a href='admin/login.php'>进入后台</a></p>";
    echo "<p style='color:red;'>安装完成后建议删除或通过 Web 服务器禁止访问 install.php。</p>";
} catch (Throwable $e) {
    http_response_code(500);
    die("安装失败: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

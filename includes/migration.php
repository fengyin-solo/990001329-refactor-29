<?php
/**
 * 数据库安装/迁移模块（唯一执行口径）
 *
 * install.php（Web 安装）与 cli_install.php（命令行/流水线/部署）都通过
 * 本模块完成初始化与迁移，保证新安装、旧库升级、重复执行结果一致：
 *
 *   1. 建库（不存在时）
 *   2. 建表（执行 database/schema.sql，幂等）
 *   3. 默认管理员（仅在不存在时创建，不覆盖已有账号密码）
 *   4. 示例数据（仅在留言表为空时插入，重复执行不产生重复数据）
 *
 * 已有留言、收藏、举报数据及管理员账号均不受影响。
 */

require_once __DIR__ . '/../config/database.php';

/**
 * 连接 MySQL 服务器并确保业务库存在
 * 连接参数统一取自 config/database.php（支持环境变量覆盖）
 */
function getInstallPDO() {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    return $pdo;
}

/**
 * 读取结构定义文件 database/schema.sql 并拆分为单条 SQL
 */
function getSchemaStatements() {
    $file = __DIR__ . '/../database/schema.sql';
    $sql = @file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("无法读取结构定义文件: $file");
    }

    // 去掉行注释后按分号拆分（schema.sql 约定语句内不含分号）
    $lines = [];
    foreach (preg_split('/\r?\n/', $sql) as $line) {
        if (preg_match('/^\s*--/', $line)) continue;
        $lines[] = $line;
    }

    $statements = [];
    foreach (explode(';', implode("\n", $lines)) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') $statements[] = $stmt;
    }
    return $statements;
}

/**
 * 执行结构迁移（幂等，可重复执行）
 * @return string[] 执行日志
 */
function migrateDatabase(PDO $pdo) {
    $logs = [];
    foreach (getSchemaStatements() as $stmt) {
        $pdo->exec($stmt);
        if (preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/i', $stmt, $m)) {
            $logs[] = "数据表 `{$m[1]}` 已就绪";
        }
    }
    return $logs;
}

/**
 * 确保默认管理员存在
 * 仅在不存在时创建（admin / admin123）；已存在则保持不变，不影响现有登录
 */
function ensureDefaultAdmin(PDO $pdo) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO `admins` (`username`, `password`) VALUES ('admin', ?)");
    $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    return $stmt->rowCount() > 0
        ? '默认管理员已创建（admin / admin123）'
        : '默认管理员 admin 已存在，保持不变';
}

/**
 * 插入示例留言数据
 * 仅在留言表为空时插入，重复执行或已有数据的库不会产生重复数据
 */
function seedSampleMessages(PDO $pdo) {
    if ((int)$pdo->query("SELECT COUNT(*) FROM `messages`")->fetchColumn() > 0) {
        return '留言表已有数据，跳过示例数据';
    }

    $sampleData = [
        ['张大爷', '13800001111', 'help', '楼道灯坏了', '3号楼2单元楼道灯已经坏了一周，晚上出行很不方便，希望能尽快维修。', null, 1],
        ['李阿姨', '13800002222', 'suggest', '建议增加健身器材', '小区广场上没有健身器材，建议物业能增加一些简单的健身设施，方便居民锻炼。', null, 1],
        ['王先生', '13800003333', 'lost', '捡到一只白色小猫', '昨天在小区门口捡到一只白色小猫，有项圈，应该是附近居民养的。联系电话联系我。', null, 1],
        ['赵女士', '13800004444', 'help', '下水道堵塞', '1号楼1单元下水道堵塞严重，污水都漫出来了，影响整栋楼居民生活，急需处理！', null, 1],
        ['孙师傅', '13800005555', 'suggest', '停车位规划建议', '小区停车位紧张，建议物业重新规划停车区域，利用闲置空地增加停车位。', null, 1],
        ['周同学', '13800006666', 'lost', '丢失蓝色书包', '今天下午在小区花园丢失一个蓝色书包，里面有课本和文具，如有拾到请联系我，万分感谢！', null, 1],
    ];

    $stmt = $pdo->prepare("INSERT INTO `messages` (`nickname`, `phone`, `type`, `title`, `content`, `image`, `status`, `views`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sampleData as $d) {
        $stmt->execute([$d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], rand(10, 200)]);
    }
    return '示例留言数据已插入（' . count($sampleData) . ' 条）';
}

/**
 * 执行完整安装/迁移流程
 * @return string[] 执行日志
 */
function runInstall() {
    $pdo = getInstallPDO();

    $logs = ['数据库 `' . DB_NAME . '` 已就绪'];
    $logs = array_merge($logs, migrateDatabase($pdo));
    $logs[] = ensureDefaultAdmin($pdo);
    $logs[] = seedSampleMessages($pdo);

    // 上传目录
    if (!is_dir(__DIR__ . '/../uploads')) {
        mkdir(__DIR__ . '/../uploads', 0755, true);
    }
    $logs[] = '上传目录 uploads 已就绪';

    return $logs;
}

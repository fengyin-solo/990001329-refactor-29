-- =============================================================
-- 社区便民留言板 - 数据库结构定义（唯一数据源）
--
-- 本文件是库表结构的唯一定义，以下入口共用此文件，保证结果一致：
--   1. Web 安装：install.php
--   2. 命令行安装/迁移：php cli_install.php（构建、流水线、部署、本地开发通用）
--   3. 手动执行：mysql -u root -p community_board < database/schema.sql
--
-- 全部语句幂等（CREATE TABLE IF NOT EXISTS），可重复执行：
-- 新安装、旧库升级、重复执行都会得到相同结构，且不影响已有数据。
--
-- 维护约定：
--   - 新增或调整表结构只修改本文件，其他入口无需改动；
--   - 本文件不包含 CREATE DATABASE / USE 语句，目标库由执行方选择；
--   - 每条语句以分号结尾，注释只用行注释（--），语句内不要出现分号。
-- =============================================================

-- 留言表
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nickname` VARCHAR(50) NOT NULL COMMENT '昵称',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
    `type` ENUM('help','suggest','lost') NOT NULL DEFAULT 'help' COMMENT '类型: help求助, suggest建议, lost失物招领',
    `title` VARCHAR(100) NOT NULL COMMENT '标题',
    `content` TEXT NOT NULL COMMENT '内容',
    `image` VARCHAR(255) DEFAULT NULL COMMENT '图片路径',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0待审核, 1已通过, 2已拒绝',
    `views` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='留言表';

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 收藏表
CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` VARCHAR(64) NOT NULL COMMENT '访客唯一标识',
    `message_id` INT UNSIGNED NOT NULL COMMENT '留言ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
    UNIQUE KEY `uk_visitor_message` (`visitor_id`, `message_id`),
    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_message_id` (`message_id`),
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

-- 举报表
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id` INT UNSIGNED NOT NULL COMMENT '被举报的留言ID',
    `visitor_id` VARCHAR(64) NOT NULL COMMENT '举报人访客标识',
    `report_type` VARCHAR(50) NOT NULL COMMENT '举报类型: spam垃圾信息, abuse辱骂攻击, illegal违法违规, porn色情低俗, other其他',
    `description` TEXT COMMENT '补充说明',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0待处理, 1已处理-已删除, 2已处理-已忽略, 3已驳回',
    `processed_by` INT UNSIGNED DEFAULT NULL COMMENT '处理人管理员ID',
    `processed_at` DATETIME DEFAULT NULL COMMENT '处理时间',
    `process_note` VARCHAR(500) DEFAULT NULL COMMENT '处理备注',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '举报时间',
    UNIQUE KEY `uk_visitor_message` (`visitor_id`, `message_id`),
    INDEX `idx_message_id` (`message_id`),
    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='举报表';

-- 基础留言与管理员表
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

CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

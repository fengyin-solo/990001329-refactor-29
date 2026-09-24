-- 收藏表
-- 兼容旧入口：原 database/migration_add_favorites.sql 指向本文件。
USE `community_board`;

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

<?php
/**
 * 默认管理员种子数据
 *
 * 仅在缺少默认账号时创建，不覆盖已有账号或密码。
 */

const DEFAULT_ADMIN_USERNAME = 'admin';
const DEFAULT_ADMIN_PASSWORD = 'admin123';

function seedDefaultAdmin(PDO $db) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT id, username FROM admins WHERE username = ? FOR UPDATE");
        $stmt->execute([DEFAULT_ADMIN_USERNAME]);
        if ($stmt->fetch()) {
            $db->commit();
            return ['created' => false, 'username' => DEFAULT_ADMIN_USERNAME];
        }

        $hash = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([DEFAULT_ADMIN_USERNAME, $hash]);

        $db->commit();
        return ['created' => true, 'username' => DEFAULT_ADMIN_USERNAME];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

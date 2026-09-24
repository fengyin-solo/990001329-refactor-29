<?php
/**
 * 示例留言数据
 *
 * 仅全新空库导入，重复执行不会向已有环境追加重复数据。
 */

function sampleMessages() {
    return [
        ['张大爷', '13800001111', 'help', '楼道灯坏了', '3号楼2单元楼道灯已经坏了一周，晚上出行很不方便，希望能尽快维修。', null, 1, 86],
        ['李阿姨', '13800002222', 'suggest', '建议增加健身器材', '小区广场上没有健身器材，建议物业能增加一些简单的健身设施，方便居民锻炼。', null, 1, 124],
        ['王先生', '13800003333', 'lost', '捡到一只白色小猫', '昨天在小区门口捡到一只白色小猫，有项圈，应该是附近居民养的。联系电话联系我。', null, 1, 67],
        ['赵女士', '13800004444', 'help', '下水道堵塞', '1号楼1单元下水道堵塞严重，污水都漫出来了，影响整栋楼居民生活，急需处理！', null, 1, 158],
        ['孙师傅', '13800005555', 'suggest', '停车位规划建议', '小区停车位紧张，建议物业重新规划停车区域，利用闲置空地增加停车位。', null, 1, 42],
        ['周同学', '13800006666', 'lost', '丢失蓝色书包', '今天下午在小区花园丢失一个蓝色书包，里面有课本和文具，如有拾到请联系我，万分感谢！', null, 1, 93],
    ];
}

function seedSampleMessages(PDO $db) {
    $lockName = 'community_board_seed_messages';
    acquireDatabaseLock($db, $lockName);

    $db->beginTransaction();
    try {
        $messageCount = (int) $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        if ($messageCount > 0) {
            $db->commit();
            releaseDatabaseLock($db, $lockName);
            return ['inserted' => false, 'count' => 0];
        }

        $stmt = $db->prepare("INSERT INTO messages (nickname, phone, type, title, content, image, status, views) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $messages = sampleMessages();
        foreach ($messages as $message) {
            $stmt->execute($message);
        }

        $db->commit();
        releaseDatabaseLock($db, $lockName);

        return ['inserted' => true, 'count' => count($messages)];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        releaseDatabaseLock($db, $lockName);
        throw $e;
    }
}

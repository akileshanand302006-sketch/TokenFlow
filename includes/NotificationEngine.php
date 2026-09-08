<?php
/**
 * TokenFlow Pro — Notification Engine
 */

require_once __DIR__ . '/Database.php';

class NotificationEngine {
    
    /**
     * Create a notification
     */
    public static function create(int $userId, string $type, string $title, string $message, string $icon = 'bi-bell', ?string $link = null): int {
        $db = Database::getInstance();
        
        return $db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => $link
        ]);
    }
    
    /**
     * Get notifications for a user
     */
    public static function getForUser(int $userId, ?string $type = null, int $limit = 20, int $offset = 0): array {
        $db = Database::getInstance();
        
        $where = 'user_id = ?';
        $params = [$userId];
        
        if ($type && $type !== 'all') {
            $where .= ' AND type = ?';
            $params[] = $type;
        }
        
        $notifications = $db->fetchAll(
            "SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
            $params
        );
        
        $unreadCount = $db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        
        return [
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount
        ];
    }
    
    /**
     * Mark notification as read
     */
    public static function markRead(int $notificationId, int $userId): bool {
        $db = Database::getInstance();
        return $db->execute(
            "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?",
            [$notificationId, $userId]
        ) > 0;
    }
    
    /**
     * Mark all as read
     */
    public static function markAllRead(int $userId): int {
        $db = Database::getInstance();
        return $db->execute(
            "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }
    
    /**
     * Get unread count
     */
    public static function getUnreadCount(int $userId): int {
        $db = Database::getInstance();
        return (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }
    
    /**
     * Broadcast to all users of a role in a branch
     */
    public static function broadcast(string $title, string $message, string $severity = 'info', ?int $branchId = null, ?string $role = null): int {
        $db = Database::getInstance();
        
        $where = 'is_active = 1';
        $params = [];
        
        if ($role) {
            $where .= ' AND role = ?';
            $params[] = $role;
        }
        
        if ($branchId) {
            $where .= ' AND org_id = (SELECT org_id FROM branches WHERE id = ?)';
            $params[] = $branchId;
        }
        
        $users = $db->fetchAll("SELECT id FROM users WHERE $where", $params);
        
        $icon = match($severity) {
            'critical' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle',
            default => 'bi-info-circle'
        };
        
        $count = 0;
        foreach ($users as $user) {
            self::create($user['id'], 'emergency', $title, $message, $icon);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Delete old notifications
     */
    public static function cleanup(int $daysOld = 30): int {
        $db = Database::getInstance();
        return $db->execute(
            "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND is_read = 1",
            [$daysOld]
        );
    }
}

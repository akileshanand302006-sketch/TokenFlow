<?php
/**
 * TokenFlow Pro — Audit Logging
 */

require_once __DIR__ . '/Database.php';

class Audit {
    
    /**
     * Log an action
     */
    public static function log(
        string $action, 
        ?string $entityType = null, 
        ?int $entityId = null, 
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            $db = Database::getInstance();
            
            $db->insert('audit_logs', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => self::getClientIP(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'description' => $description
            ]);
        } catch (\Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get audit logs with filters
     */
    public static function getLogs(array $filters = [], int $page = 1, int $perPage = 20): array {
        $db = Database::getInstance();
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['action'])) {
            $where[] = 'a.action = ?';
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = ?';
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = 'a.entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(a.description LIKE ? OR a.action LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        $total = $db->fetchColumn(
            "SELECT COUNT(*) FROM audit_logs a WHERE $whereClause",
            $params
        );
        
        $logs = $db->fetchAll(
            "SELECT a.*, u.full_name as user_name, u.role as user_role
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE $whereClause
             ORDER BY a.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );
        
        return [
            'logs' => $logs,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP(): string {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                    return trim($ip);
                }
            }
        }
        
        return '0.0.0.0';
    }
}

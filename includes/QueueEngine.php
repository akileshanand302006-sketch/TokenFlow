<?php
/**
 * TokenFlow Pro — Queue Engine
 * Real-time queue status, monitoring, and operations
 */

require_once __DIR__ . '/Database.php';

class QueueEngine {
    
    /**
     * Get live queue status for a branch
     */
    public static function getLiveStatus(int $branchId): array {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        
        // Get all departments in this branch
        $departments = $db->fetchAll(
            "SELECT d.*, 
                    (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = ? AND t.status = 'waiting') as waiting_count,
                    (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = ? AND t.status = 'serving') as serving_count,
                    (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = ? AND t.status = 'completed') as completed_count,
                    (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = ? AND t.status = 'skipped') as skipped_count,
                    (SELECT AVG(actual_wait_minutes) FROM tokens t WHERE t.department_id = d.id AND t.date = ? AND t.status = 'completed' AND actual_wait_minutes IS NOT NULL) as avg_wait,
                    (SELECT AVG(actual_service_minutes) FROM tokens t WHERE t.department_id = d.id AND t.date = ? AND t.status = 'completed' AND actual_service_minutes IS NOT NULL) as avg_service
             FROM departments d
             WHERE d.branch_id = ? AND d.is_active = 1
             ORDER BY d.sort_order, d.name",
            [$today, $today, $today, $today, $today, $today, $branchId]
        );
        
        // Get counters with current status
        $counters = $db->fetchAll(
            "SELECT c.*, 
                    t.display_number as current_token_display,
                    t.status as current_token_status,
                    u.full_name as staff_name,
                    sa.department_id,
                    d.name as department_name
             FROM counters c
             LEFT JOIN tokens t ON c.current_token_id = t.id
             LEFT JOIN staff_assignments sa ON sa.counter_id = c.id AND sa.is_online = 1
             LEFT JOIN users u ON sa.user_id = u.id
             LEFT JOIN departments d ON sa.department_id = d.id
             WHERE c.branch_id = ? AND c.is_active = 1
             ORDER BY c.number",
            [$branchId]
        );
        
        // Get currently serving tokens
        $serving = $db->fetchAll(
            "SELECT t.display_number, t.status, c.number as counter_number, c.name as counter_name,
                    d.name as department_name, s.name as service_name
             FROM tokens t
             JOIN counters c ON t.counter_id = c.id
             JOIN departments d ON t.department_id = d.id
             JOIN services s ON t.service_id = s.id
             WHERE t.branch_id = ? AND t.date = ? AND t.status = 'serving'
             ORDER BY c.number",
            [$branchId, $today]
        );
        
        // Aggregate stats
        $totalWaiting = array_sum(array_column($departments, 'waiting_count'));
        $totalServing = array_sum(array_column($departments, 'serving_count'));
        $totalCompleted = array_sum(array_column($departments, 'completed_count'));
        $totalSkipped = array_sum(array_column($departments, 'skipped_count'));
        $activeCounters = count(array_filter($counters, fn($c) => $c['status'] === 'open'));
        
        // Overall average wait
        $avgWait = $db->fetchColumn(
            "SELECT AVG(actual_wait_minutes) FROM tokens 
             WHERE branch_id = ? AND date = ? AND status = 'completed' AND actual_wait_minutes IS NOT NULL",
            [$branchId, $today]
        );
        
        return [
            'branch_id' => $branchId,
            'date' => $today,
            'departments' => $departments,
            'counters' => $counters,
            'serving' => $serving,
            'stats' => [
                'total_waiting' => (int)$totalWaiting,
                'total_serving' => (int)$totalServing,
                'total_completed' => (int)$totalCompleted,
                'total_skipped' => (int)$totalSkipped,
                'total_tokens' => (int)($totalWaiting + $totalServing + $totalCompleted + $totalSkipped),
                'active_counters' => $activeCounters,
                'avg_wait_minutes' => round($avgWait ?? 0, 1)
            ]
        ];
    }
    
    /**
     * Get queue for a specific department
     */
    public static function getDepartmentQueue(int $departmentId): array {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        
        $waiting = $db->fetchAll(
            "SELECT t.id, t.display_number, t.type, t.priority_score, t.estimated_wait_minutes,
                    t.created_at, t.customer_name, u.full_name as user_name,
                    s.name as service_name
             FROM tokens t
             LEFT JOIN users u ON t.user_id = u.id
             JOIN services s ON t.service_id = s.id
             WHERE t.department_id = ? AND t.date = ? AND t.status = 'waiting'
             ORDER BY t.priority_score DESC, t.id ASC",
            [$departmentId, $today]
        );
        
        $serving = $db->fetchAll(
            "SELECT t.id, t.display_number, t.type, t.service_start_time,
                    t.customer_name, u.full_name as user_name,
                    s.name as service_name, c.number as counter_number,
                    staff.full_name as staff_name
             FROM tokens t
             LEFT JOIN users u ON t.user_id = u.id
             JOIN services s ON t.service_id = s.id
             LEFT JOIN counters c ON t.counter_id = c.id
             LEFT JOIN users staff ON t.served_by = staff.id
             WHERE t.department_id = ? AND t.date = ? AND t.status = 'serving'
             ORDER BY t.service_start_time",
            [$departmentId, $today]
        );
        
        return [
            'waiting' => $waiting,
            'serving' => $serving,
            'waiting_count' => count($waiting),
            'serving_count' => count($serving)
        ];
    }
    
    /**
     * Get queue for public display
     */
    public static function getPublicDisplay(int $branchId): array {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        
        // Currently serving
        $serving = $db->fetchAll(
            "SELECT t.display_number, c.number as counter_number, c.name as counter_name,
                    d.name as department_name, d.color as dept_color
             FROM tokens t
             JOIN counters c ON t.counter_id = c.id
             JOIN departments d ON t.department_id = d.id
             WHERE t.branch_id = ? AND t.date = ? AND t.status = 'serving'
             ORDER BY c.number",
            [$branchId, $today]
        );
        
        // Next tokens
        $next = $db->fetchAll(
            "SELECT t.display_number, d.name as department_name, d.color as dept_color
             FROM tokens t
             JOIN departments d ON t.department_id = d.id
             WHERE t.branch_id = ? AND t.date = ? AND t.status = 'waiting'
             ORDER BY t.priority_score DESC, t.id ASC
             LIMIT 10",
            [$branchId, $today]
        );
        
        // Stats
        $stats = [
            'total_waiting' => $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens WHERE branch_id = ? AND date = ? AND status = 'waiting'",
                [$branchId, $today]
            ),
            'total_completed' => $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens WHERE branch_id = ? AND date = ? AND status = 'completed'",
                [$branchId, $today]
            )
        ];
        
        return [
            'serving' => $serving,
            'next' => $next,
            'stats' => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get queue health for a department
     */
    public static function getQueueHealth(int $departmentId): string {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        
        $waitingCount = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens WHERE department_id = ? AND date = ? AND status = 'waiting'",
            [$departmentId, $today]
        );
        
        $activeCounters = $db->fetchColumn(
            "SELECT COUNT(DISTINCT c.id) FROM counters c
             JOIN staff_assignments sa ON sa.counter_id = c.id
             WHERE sa.department_id = ? AND c.status = 'open' AND sa.is_online = 1",
            [$departmentId]
        );
        
        $activeCounters = max(1, $activeCounters);
        $ratio = $waitingCount / $activeCounters;
        
        if ($ratio <= 3) return 'low';
        if ($ratio <= 7) return 'moderate';
        if ($ratio <= 15) return 'high';
        return 'critical';
    }
    
    /**
     * Get counter details for staff
     */
    public static function getCounterForStaff(int $staffId): ?array {
        $db = Database::getInstance();
        
        $assignment = $db->fetchOne(
            "SELECT sa.*, c.number as counter_number, c.name as counter_name, c.status as counter_status,
                    c.current_token_id, d.name as department_name, d.id as department_id,
                    b.name as branch_name, b.id as branch_id
             FROM staff_assignments sa
             JOIN counters c ON sa.counter_id = c.id
             JOIN departments d ON sa.department_id = d.id
             JOIN branches b ON sa.branch_id = b.id
             WHERE sa.user_id = ? AND sa.is_online = 1
             ORDER BY sa.id DESC LIMIT 1",
            [$staffId]
        );
        
        if (!$assignment) return null;
        
        // Get current token if any
        if ($assignment['current_token_id']) {
            $assignment['current_token'] = $db->fetchOne(
                "SELECT t.*, u.full_name as customer_full_name, s.name as service_name,
                        s.avg_service_time
                 FROM tokens t
                 LEFT JOIN users u ON t.user_id = u.id
                 JOIN services s ON t.service_id = s.id
                 WHERE t.id = ?",
                [$assignment['current_token_id']]
            );
        }
        
        // Get queue count
        $today = date('Y-m-d');
        $assignment['queue_count'] = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens WHERE department_id = ? AND date = ? AND status = 'waiting'",
            [$assignment['department_id'], $today]
        );
        
        // Get today's stats for this staff
        $assignment['today_stats'] = [
            'served' => $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens WHERE served_by = ? AND date = ? AND status = 'completed'",
                [$staffId, $today]
            ),
            'avg_service_time' => round($db->fetchColumn(
                "SELECT AVG(actual_service_minutes) FROM tokens WHERE served_by = ? AND date = ? AND status = 'completed' AND actual_service_minutes IS NOT NULL",
                [$staffId, $today]
            ) ?? 0, 1),
            'skipped' => $db->fetchColumn(
                "SELECT COUNT(*) FROM token_history WHERE changed_by = ? AND to_status = 'skipped' AND DATE(created_at) = ?",
                [$staffId, $today]
            )
        ];
        
        return $assignment;
    }
    
    /**
     * Set counter status (open/close/break)
     */
    public static function setCounterStatus(int $counterId, string $status, int $staffId): array {
        $db = Database::getInstance();
        
        $validStatuses = ['open', 'closed', 'break', 'maintenance'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }
        
        $db->execute("UPDATE counters SET status = ? WHERE id = ?", [$status, $counterId]);
        
        if ($status === 'open') {
            $db->execute(
                "UPDATE staff_assignments SET is_online = 1 WHERE counter_id = ? AND user_id = ?",
                [$counterId, $staffId]
            );
        } else {
            $db->execute(
                "UPDATE staff_assignments SET is_online = 0 WHERE counter_id = ? AND user_id = ?",
                [$counterId, $staffId]
            );
        }
        
        Audit::log('COUNTER_STATUS_CHANGED', 'counter', $counterId, "Counter status changed to $status");
        
        return ['success' => true, 'message' => "Counter set to $status."];
    }
}

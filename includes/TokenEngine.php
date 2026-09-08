<?php
/**
 * TokenFlow Pro — Token Engine
 * Core token generation, lifecycle management, and operations
 */

require_once __DIR__ . '/Database.php';

class TokenEngine {
    
    /**
     * Generate a new token
     */
    public static function generate(array $data): array {
        $db = Database::getInstance();
        
        $branchId = (int)$data['branch_id'];
        $departmentId = (int)$data['department_id'];
        $serviceId = (int)$data['service_id'];
        $userId = $data['user_id'] ?? null;
        $type = $data['type'] ?? 'normal';
        $customerName = $data['customer_name'] ?? null;
        $customerPhone = $data['customer_phone'] ?? null;
        $isVirtual = $data['is_virtual'] ?? false;
        
        // Get department for token prefix
        $dept = $db->fetchOne(
            "SELECT token_prefix, daily_token_limit FROM departments WHERE id = ? AND is_active = 1",
            [$departmentId]
        );
        
        if (!$dept) {
            return ['success' => false, 'message' => 'Department not found or inactive.'];
        }
        
        $today = date('Y-m-d');
        
        // Get next token number for today in this department
        $lastToken = $db->fetchColumn(
            "SELECT MAX(CAST(SUBSTRING(token_number, LENGTH(?) + 1) AS UNSIGNED)) 
             FROM tokens WHERE department_id = ? AND date = ?",
            [$dept['token_prefix'], $departmentId, $today]
        );
        
        $nextNumber = ($lastToken ?? 0) + 1;
        
        // Check daily limit
        if ($nextNumber > $dept['daily_token_limit']) {
            return ['success' => false, 'message' => 'Daily token limit reached for this department.'];
        }
        
        $tokenNumber = $dept['token_prefix'] . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $displayNumber = $dept['token_prefix'] . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        // Calculate priority score
        $priorityScore = self::calculatePriorityScore($type);
        
        // Count position in queue
        $positionInQueue = $db->fetchColumn(
            "SELECT COUNT(*) + 1 FROM tokens 
             WHERE department_id = ? AND date = ? AND status = 'waiting'
             AND (priority_score < ? OR (priority_score = ? AND id < LAST_INSERT_ID()))",
            [$departmentId, $today, $priorityScore, $priorityScore]
        );
        
        // Estimate wait time
        $estimatedWait = self::estimateWaitForNewToken($departmentId, $branchId, $positionInQueue);
        
        // Generate QR code data
        $qrData = json_encode([
            'token' => $displayNumber,
            'branch' => $branchId,
            'date' => $today,
            'hash' => substr(md5($tokenNumber . $today . $branchId), 0, 8)
        ]);
        
        $db->beginTransaction();
        
        try {
            // Insert token
            $tokenId = $db->insert('tokens', [
                'token_number' => $tokenNumber,
                'display_number' => $displayNumber,
                'user_id' => $userId,
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'service_id' => $serviceId,
                'type' => $type,
                'status' => 'waiting',
                'priority_score' => $priorityScore,
                'position_in_queue' => $positionInQueue,
                'estimated_wait_minutes' => $estimatedWait,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'qr_code' => $qrData,
                'is_virtual' => $isVirtual ? 1 : 0,
                'date' => $today
            ]);
            
            // Record history
            $db->insert('token_history', [
                'token_id' => $tokenId,
                'from_status' => null,
                'to_status' => 'waiting',
                'changed_by' => $userId,
                'notes' => 'Token generated'
            ]);
            
            // Create notification for user
            if ($userId) {
                $db->insert('notifications', [
                    'user_id' => $userId,
                    'type' => 'token',
                    'title' => 'Token Generated: ' . $displayNumber,
                    'message' => "Your token $displayNumber has been generated. Estimated wait: ~{$estimatedWait} min.",
                    'icon' => 'bi-ticket-perforated',
                    'link' => 'pages/customer/active-token.php?id=' . $tokenId
                ]);
            }
            
            $db->commit();
            
            // Get full token data
            $token = $db->fetchOne(
                "SELECT t.*, d.name as department_name, s.name as service_name, 
                        b.name as branch_name, d.token_prefix
                 FROM tokens t
                 JOIN departments d ON t.department_id = d.id
                 JOIN services s ON t.service_id = s.id
                 JOIN branches b ON t.branch_id = b.id
                 WHERE t.id = ?",
                [$tokenId]
            );
            
            // Count people ahead
            $peopleAhead = $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens 
                 WHERE department_id = ? AND date = ? AND status = 'waiting' AND id < ?
                 ORDER BY priority_score DESC, id ASC",
                [$departmentId, $today, $tokenId]
            );
            
            $token['people_ahead'] = $peopleAhead;
            
            Audit::log('TOKEN_CREATED', 'token', $tokenId, "Token $displayNumber generated");
            
            return [
                'success' => true,
                'message' => 'Token generated successfully!',
                'token' => $token
            ];
            
        } catch (\Exception $e) {
            $db->rollback();
            error_log("Token generation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate token. Please try again.'];
        }
    }
    
    /**
     * Call the next token for a counter
     */
    public static function callNext(int $counterId, int $staffId): array {
        $db = Database::getInstance();
        
        // Get counter info and assignment
        $counter = $db->fetchOne(
            "SELECT c.*, sa.department_id 
             FROM counters c
             JOIN staff_assignments sa ON sa.counter_id = c.id AND sa.user_id = ?
             WHERE c.id = ? AND c.status = 'open'",
            [$staffId, $counterId]
        );
        
        if (!$counter) {
            return ['success' => false, 'message' => 'Counter not found or not assigned to you.'];
        }
        
        // Complete any currently serving token
        if ($counter['current_token_id']) {
            self::completeToken($counter['current_token_id'], $staffId);
        }
        
        $today = date('Y-m-d');
        
        // Get next waiting token (priority first, then FIFO)
        $nextToken = $db->fetchOne(
            "SELECT t.* FROM tokens t
             WHERE t.department_id = ? AND t.date = ? AND t.status = 'waiting'
             ORDER BY t.priority_score DESC, t.id ASC
             LIMIT 1",
            [$counter['department_id'], $today]
        );
        
        if (!$nextToken) {
            return ['success' => false, 'message' => 'No more tokens waiting in queue.', 'queue_empty' => true];
        }
        
        $db->beginTransaction();
        
        try {
            $now = date('Y-m-d H:i:s');
            $waitMinutes = round((strtotime($now) - strtotime($nextToken['created_at'])) / 60);
            
            // Update token status
            $db->execute(
                "UPDATE tokens SET status = 'serving', counter_id = ?, served_by = ?,
                 service_start_time = ?, actual_wait_minutes = ?, called_at = ?
                 WHERE id = ?",
                [$counterId, $staffId, $now, $waitMinutes, $now, $nextToken['id']]
            );
            
            // Update counter
            $db->execute(
                "UPDATE counters SET current_token_id = ? WHERE id = ?",
                [$nextToken['id'], $counterId]
            );
            
            // Record history
            $db->insert('token_history', [
                'token_id' => $nextToken['id'],
                'from_status' => 'waiting',
                'to_status' => 'serving',
                'changed_by' => $staffId,
                'counter_id' => $counterId,
                'notes' => 'Called to counter ' . $counter['number']
            ]);
            
            // Notify the customer
            if ($nextToken['user_id']) {
                $db->insert('notifications', [
                    'user_id' => $nextToken['user_id'],
                    'type' => 'token',
                    'title' => 'Your turn! ' . $nextToken['display_number'],
                    'message' => "Please proceed to Counter {$counter['number']}.",
                    'icon' => 'bi-bell-fill',
                    'link' => 'pages/customer/active-token.php?id=' . $nextToken['id']
                ]);
            }
            
            // Start service record
            $db->insert('service_records', [
                'token_id' => $nextToken['id'],
                'staff_id' => $staffId,
                'counter_id' => $counterId,
                'service_id' => $nextToken['service_id'],
                'start_time' => $now
            ]);
            
            $db->commit();
            
            // Get full token data
            $token = $db->fetchOne(
                "SELECT t.*, d.name as department_name, s.name as service_name,
                        u.full_name as customer_full_name
                 FROM tokens t
                 JOIN departments d ON t.department_id = d.id
                 JOIN services s ON t.service_id = s.id
                 LEFT JOIN users u ON t.user_id = u.id
                 WHERE t.id = ?",
                [$nextToken['id']]
            );
            
            Audit::log('TOKEN_CALLED', 'token', $nextToken['id'], 
                "Token {$nextToken['display_number']} called to Counter {$counter['number']}");
            
            return [
                'success' => true,
                'message' => 'Token called successfully.',
                'token' => $token,
                'counter_number' => $counter['number']
            ];
            
        } catch (\Exception $e) {
            $db->rollback();
            error_log("Call next failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to call next token.'];
        }
    }
    
    /**
     * Complete a token (mark as served)
     */
    public static function completeToken(int $tokenId, int $staffId): array {
        $db = Database::getInstance();
        
        $token = $db->fetchOne("SELECT * FROM tokens WHERE id = ? AND status = 'serving'", [$tokenId]);
        
        if (!$token) {
            return ['success' => false, 'message' => 'Token not found or not currently being served.'];
        }
        
        $now = date('Y-m-d H:i:s');
        $serviceMinutes = $token['service_start_time'] 
            ? round((strtotime($now) - strtotime($token['service_start_time'])) / 60) 
            : 0;
        
        $db->beginTransaction();
        
        try {
            $db->execute(
                "UPDATE tokens SET status = 'completed', service_end_time = ?, 
                 actual_service_minutes = ?, completed_at = ? WHERE id = ?",
                [$now, $serviceMinutes, $now, $tokenId]
            );
            
            // Clear counter
            $db->execute(
                "UPDATE counters SET current_token_id = NULL WHERE current_token_id = ?",
                [$tokenId]
            );
            
            // Update service record
            $db->execute(
                "UPDATE service_records SET end_time = ?, duration_minutes = ? 
                 WHERE token_id = ? AND end_time IS NULL",
                [$now, $serviceMinutes, $tokenId]
            );
            
            // Record history
            $db->insert('token_history', [
                'token_id' => $tokenId,
                'from_status' => 'serving',
                'to_status' => 'completed',
                'changed_by' => $staffId,
                'counter_id' => $token['counter_id'],
                'notes' => "Service completed in {$serviceMinutes} min"
            ]);
            
            $db->commit();
            
            Audit::log('TOKEN_COMPLETED', 'token', $tokenId, 
                "Token {$token['display_number']} completed ({$serviceMinutes} min)");
            
            return [
                'success' => true,
                'message' => 'Token completed.',
                'service_minutes' => $serviceMinutes
            ];
            
        } catch (\Exception $e) {
            $db->rollback();
            return ['success' => false, 'message' => 'Failed to complete token.'];
        }
    }
    
    /**
     * Skip a token
     */
    public static function skipToken(int $tokenId, int $staffId, ?string $reason = null): array {
        $db = Database::getInstance();
        
        $token = $db->fetchOne(
            "SELECT * FROM tokens WHERE id = ? AND status IN ('waiting', 'serving')", 
            [$tokenId]
        );
        
        if (!$token) {
            return ['success' => false, 'message' => 'Token not found or already processed.'];
        }
        
        $db->beginTransaction();
        
        try {
            $db->execute(
                "UPDATE tokens SET status = 'skipped' WHERE id = ?",
                [$tokenId]
            );
            
            if ($token['status'] === 'serving') {
                $db->execute(
                    "UPDATE counters SET current_token_id = NULL WHERE current_token_id = ?",
                    [$tokenId]
                );
            }
            
            $db->insert('token_history', [
                'token_id' => $tokenId,
                'from_status' => $token['status'],
                'to_status' => 'skipped',
                'changed_by' => $staffId,
                'counter_id' => $token['counter_id'],
                'notes' => $reason ?? 'Customer not present'
            ]);
            
            if ($token['user_id']) {
                $db->insert('notifications', [
                    'user_id' => $token['user_id'],
                    'type' => 'token',
                    'title' => 'Token Skipped: ' . $token['display_number'],
                    'message' => 'Your token was skipped. Reason: ' . ($reason ?? 'Not present'),
                    'icon' => 'bi-skip-forward'
                ]);
            }
            
            $db->commit();
            
            Audit::log('TOKEN_SKIPPED', 'token', $tokenId, "Token {$token['display_number']} skipped");
            
            return ['success' => true, 'message' => 'Token skipped.'];
            
        } catch (\Exception $e) {
            $db->rollback();
            return ['success' => false, 'message' => 'Failed to skip token.'];
        }
    }
    
    /**
     * Transfer a token to another counter/department
     */
    public static function transferToken(int $tokenId, int $targetCounterId, int $staffId, ?string $reason = null): array {
        $db = Database::getInstance();
        
        $token = $db->fetchOne(
            "SELECT * FROM tokens WHERE id = ? AND status IN ('waiting', 'serving')", 
            [$tokenId]
        );
        
        if (!$token) {
            return ['success' => false, 'message' => 'Token not found or already processed.'];
        }
        
        $targetCounter = $db->fetchOne("SELECT * FROM counters WHERE id = ? AND status = 'open'", [$targetCounterId]);
        
        if (!$targetCounter) {
            return ['success' => false, 'message' => 'Target counter not available.'];
        }
        
        $db->beginTransaction();
        
        try {
            // Get target department from staff assignment
            $targetDept = $db->fetchColumn(
                "SELECT department_id FROM staff_assignments WHERE counter_id = ? LIMIT 1",
                [$targetCounterId]
            );
            
            $db->execute(
                "UPDATE tokens SET status = 'waiting', counter_id = NULL, served_by = NULL,
                 service_start_time = NULL, department_id = COALESCE(?, department_id)
                 WHERE id = ?",
                [$targetDept, $tokenId]
            );
            
            if ($token['counter_id']) {
                $db->execute(
                    "UPDATE counters SET current_token_id = NULL WHERE current_token_id = ?",
                    [$tokenId]
                );
            }
            
            $db->insert('token_transfers', [
                'token_id' => $tokenId,
                'from_counter_id' => $token['counter_id'] ?? 0,
                'to_counter_id' => $targetCounterId,
                'from_department_id' => $token['department_id'],
                'to_department_id' => $targetDept,
                'transferred_by' => $staffId,
                'reason' => $reason
            ]);
            
            $db->insert('token_history', [
                'token_id' => $tokenId,
                'from_status' => $token['status'],
                'to_status' => 'transferred',
                'changed_by' => $staffId,
                'counter_id' => $targetCounterId,
                'notes' => 'Transferred to Counter ' . $targetCounter['number']
            ]);
            
            $db->commit();
            
            Audit::log('TOKEN_TRANSFERRED', 'token', $tokenId, 
                "Token {$token['display_number']} transferred to Counter {$targetCounter['number']}");
            
            return ['success' => true, 'message' => 'Token transferred successfully.'];
            
        } catch (\Exception $e) {
            $db->rollback();
            return ['success' => false, 'message' => 'Failed to transfer token.'];
        }
    }
    
    /**
     * Recall a token (re-announce)
     */
    public static function recallToken(int $tokenId, int $staffId): array {
        $db = Database::getInstance();
        
        $token = $db->fetchOne(
            "SELECT t.*, c.number as counter_number FROM tokens t
             LEFT JOIN counters c ON t.counter_id = c.id
             WHERE t.id = ? AND t.status = 'serving'", 
            [$tokenId]
        );
        
        if (!$token) {
            return ['success' => false, 'message' => 'Token not found or not being served.'];
        }
        
        $db->execute("UPDATE tokens SET called_at = NOW() WHERE id = ?", [$tokenId]);
        
        if ($token['user_id']) {
            $db->insert('notifications', [
                'user_id' => $token['user_id'],
                'type' => 'token',
                'title' => 'Recall: ' . $token['display_number'],
                'message' => "You are being called again. Please proceed to Counter {$token['counter_number']}.",
                'icon' => 'bi-megaphone'
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Token recalled.',
            'token' => $token
        ];
    }
    
    /**
     * Cancel a token (customer-initiated)
     */
    public static function cancelToken(int $tokenId, int $userId): array {
        $db = Database::getInstance();
        
        $token = $db->fetchOne(
            "SELECT * FROM tokens WHERE id = ? AND user_id = ? AND status = 'waiting'", 
            [$tokenId, $userId]
        );
        
        if (!$token) {
            return ['success' => false, 'message' => 'Token not found or cannot be cancelled.'];
        }
        
        $db->execute("UPDATE tokens SET status = 'cancelled' WHERE id = ?", [$tokenId]);
        
        $db->insert('token_history', [
            'token_id' => $tokenId,
            'from_status' => 'waiting',
            'to_status' => 'cancelled',
            'changed_by' => $userId,
            'notes' => 'Cancelled by customer'
        ]);
        
        Audit::log('TOKEN_CANCELLED', 'token', $tokenId, "Token {$token['display_number']} cancelled by customer");
        
        return ['success' => true, 'message' => 'Token cancelled.'];
    }
    
    /**
     * Get token details with related data
     */
    public static function getTokenDetails(int $tokenId): ?array {
        $db = Database::getInstance();
        
        $token = $db->fetchOne(
            "SELECT t.*, 
                    d.name as department_name, d.token_prefix, d.icon as dept_icon, d.color as dept_color,
                    s.name as service_name, s.avg_service_time,
                    b.name as branch_name,
                    c.number as counter_number, c.name as counter_name,
                    u.full_name as customer_full_name, u.email as customer_email, u.phone as customer_phone_user,
                    staff.full_name as staff_name
             FROM tokens t
             JOIN departments d ON t.department_id = d.id
             JOIN services s ON t.service_id = s.id
             JOIN branches b ON t.branch_id = b.id
             LEFT JOIN counters c ON t.counter_id = c.id
             LEFT JOIN users u ON t.user_id = u.id
             LEFT JOIN users staff ON t.served_by = staff.id
             WHERE t.id = ?",
            [$tokenId]
        );
        
        if (!$token) return null;
        
        // Get people ahead
        if ($token['status'] === 'waiting') {
            $token['people_ahead'] = $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens 
                 WHERE department_id = ? AND date = ? AND status = 'waiting' AND 
                 (priority_score > ? OR (priority_score = ? AND id < ?))
                 ORDER BY priority_score DESC, id ASC",
                [$token['department_id'], $token['date'], $token['priority_score'], $token['priority_score'], $token['id']]
            );
        } else {
            $token['people_ahead'] = 0;
        }
        
        // Get history
        $token['history'] = $db->fetchAll(
            "SELECT th.*, u.full_name as changed_by_name, c.number as counter_number
             FROM token_history th
             LEFT JOIN users u ON th.changed_by = u.id
             LEFT JOIN counters c ON th.counter_id = c.id
             WHERE th.token_id = ?
             ORDER BY th.created_at ASC",
            [$tokenId]
        );
        
        return $token;
    }
    
    /**
     * Get active token for a user
     */
    public static function getActiveTokenForUser(int $userId): ?array {
        $db = Database::getInstance();
        
        $today = date('Y-m-d');
        
        $token = $db->fetchOne(
            "SELECT t.*, d.name as department_name, d.icon as dept_icon, d.color as dept_color,
                    s.name as service_name, b.name as branch_name,
                    c.number as counter_number
             FROM tokens t
             JOIN departments d ON t.department_id = d.id
             JOIN services s ON t.service_id = s.id
             JOIN branches b ON t.branch_id = b.id
             LEFT JOIN counters c ON t.counter_id = c.id
             WHERE t.user_id = ? AND t.date = ? AND t.status IN ('waiting', 'serving')
             ORDER BY t.id DESC LIMIT 1",
            [$userId, $today]
        );
        
        if ($token && $token['status'] === 'waiting') {
            $token['people_ahead'] = $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens 
                 WHERE department_id = ? AND date = ? AND status = 'waiting'
                 AND (priority_score > ? OR (priority_score = ? AND id < ?))
                ",
                [$token['department_id'], $today, $token['priority_score'], $token['priority_score'], $token['id']]
            );
            
            $token['estimated_wait_minutes'] = self::estimateWaitForNewToken(
                $token['department_id'], $token['branch_id'], $token['people_ahead'] + 1
            );
        }
        
        return $token;
    }
    
    /**
     * Calculate priority score
     */
    private static function calculatePriorityScore(string $type): int {
        $scores = [
            'normal' => 0,
            'appointment' => 50,
            'priority' => 100,
            'vip' => 200
        ];
        return $scores[$type] ?? 0;
    }
    
    /**
     * Estimate wait time for a new token at given position
     */
    private static function estimateWaitForNewToken(int $departmentId, int $branchId, int $position): int {
        $db = Database::getInstance();
        
        // Get average service time from recent completed tokens
        $avgServiceTime = $db->fetchColumn(
            "SELECT AVG(actual_service_minutes) FROM tokens 
             WHERE department_id = ? AND status = 'completed' 
             AND actual_service_minutes > 0
             AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            [$departmentId]
        );
        
        if (!$avgServiceTime) {
            // Fallback to service configuration
            $avgServiceTime = $db->fetchColumn(
                "SELECT AVG(avg_service_time) FROM services WHERE department_id = ?",
                [$departmentId]
            );
        }
        
        $avgServiceTime = $avgServiceTime ?: 10; // Default 10 minutes
        
        // Count active counters for this department
        $activeCounters = $db->fetchColumn(
            "SELECT COUNT(DISTINCT c.id) FROM counters c
             JOIN staff_assignments sa ON sa.counter_id = c.id
             WHERE sa.department_id = ? AND c.status = 'open' AND sa.is_online = 1",
            [$departmentId]
        );
        
        $activeCounters = max(1, $activeCounters);
        
        // Estimated wait = (position * avgServiceTime) / activeCounters
        return max(1, (int) ceil(($position * $avgServiceTime) / $activeCounters));
    }
    
    /**
     * Get user token history
     */
    public static function getUserHistory(int $userId, int $page = 1, int $perPage = 20): array {
        $db = Database::getInstance();
        
        $offset = ($page - 1) * $perPage;
        
        $total = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens WHERE user_id = ?",
            [$userId]
        );
        
        $tokens = $db->fetchAll(
            "SELECT t.*, d.name as department_name, s.name as service_name,
                    b.name as branch_name, c.number as counter_number
             FROM tokens t
             JOIN departments d ON t.department_id = d.id
             JOIN services s ON t.service_id = s.id
             JOIN branches b ON t.branch_id = b.id
             LEFT JOIN counters c ON t.counter_id = c.id
             WHERE t.user_id = ?
             ORDER BY t.created_at DESC
             LIMIT $perPage OFFSET $offset",
            [$userId]
        );
        
        return [
            'tokens' => $tokens,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage)
        ];
    }
}

<?php
/**
 * TokenFlow Pro — Queue Intelligence
 * Prediction, optimization, and analytics algorithms
 */

require_once __DIR__ . '/Database.php';

class QueueIntelligence {
    
    /**
     * Estimate wait time for a specific token
     */
    public static function estimateWaitTime(int $tokenId): int {
        $db = Database::getInstance();
        
        $token = $db->fetchOne(
            "SELECT t.*, d.id as dept_id FROM tokens t
             JOIN departments d ON t.department_id = d.id
             WHERE t.id = ?",
            [$tokenId]
        );
        
        if (!$token || $token['status'] !== 'waiting') return 0;
        
        $today = date('Y-m-d');
        
        // Count tokens ahead
        $ahead = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens 
             WHERE department_id = ? AND date = ? AND status = 'waiting'
             AND (priority_score > ? OR (priority_score = ? AND id < ?))",
            [$token['dept_id'], $today, $token['priority_score'], $token['priority_score'], $token['id']]
        );
        
        // Get weighted average service time (recent data weighted more)
        $recentAvg = $db->fetchColumn(
            "SELECT AVG(actual_service_minutes) FROM tokens 
             WHERE department_id = ? AND status = 'completed' AND actual_service_minutes > 0
             AND date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)",
            [$token['dept_id']]
        );
        
        $historicalAvg = $db->fetchColumn(
            "SELECT AVG(actual_service_minutes) FROM tokens 
             WHERE department_id = ? AND status = 'completed' AND actual_service_minutes > 0
             AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            [$token['dept_id']]
        );
        
        // Weighted: 70% recent, 30% historical
        $avgServiceTime = ($recentAvg ? $recentAvg * 0.7 : 0) + ($historicalAvg ? $historicalAvg * 0.3 : 0);
        
        if ($avgServiceTime <= 0) {
            $avgServiceTime = $db->fetchColumn(
                "SELECT AVG(avg_service_time) FROM services WHERE department_id = ?",
                [$token['dept_id']]
            ) ?: 10;
        }
        
        // Count active counters
        $activeCounters = $db->fetchColumn(
            "SELECT COUNT(DISTINCT c.id) FROM counters c
             JOIN staff_assignments sa ON sa.counter_id = c.id
             WHERE sa.department_id = ? AND c.status = 'open' AND sa.is_online = 1",
            [$token['dept_id']]
        );
        $activeCounters = max(1, $activeCounters);
        
        // Apply peak hour factor
        $peakFactor = self::getPeakFactor($token['branch_id']);
        
        $estimated = ceil((($ahead + 1) * $avgServiceTime * $peakFactor) / $activeCounters);
        
        return max(1, (int)$estimated);
    }
    
    /**
     * Calculate queue health for a department
     */
    public static function calculateQueueHealth(int $departmentId): array {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        
        $waiting = $db->fetchColumn(
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
        
        $ratio = $waiting / $activeCounters;
        
        // Get SLA threshold
        $slaMaxWait = $db->fetchColumn(
            "SELECT AVG(sr.max_wait_minutes) FROM sla_rules sr
             JOIN services s ON sr.service_id = s.id
             WHERE s.department_id = ?",
            [$departmentId]
        ) ?: 30;
        
        $avgWait = $db->fetchColumn(
            "SELECT AVG(actual_wait_minutes) FROM tokens 
             WHERE department_id = ? AND date = ? AND status = 'completed' AND actual_wait_minutes IS NOT NULL",
            [$departmentId, $today]
        ) ?: 0;
        
        // Determine health
        if ($ratio <= 3 && $avgWait <= $slaMaxWait * 0.5) {
            $health = 'low';
            $score = 95;
        } elseif ($ratio <= 7 && $avgWait <= $slaMaxWait * 0.75) {
            $health = 'moderate';
            $score = 70;
        } elseif ($ratio <= 15 && $avgWait <= $slaMaxWait) {
            $health = 'high';
            $score = 40;
        } else {
            $health = 'critical';
            $score = 15;
        }
        
        return [
            'health' => $health,
            'score' => $score,
            'waiting' => (int)$waiting,
            'active_counters' => (int)$activeCounters,
            'ratio' => round($ratio, 1),
            'avg_wait' => round($avgWait, 1),
            'sla_threshold' => (int)$slaMaxWait
        ];
    }
    
    /**
     * Detect peak hours for a branch
     */
    public static function detectPeakHours(int $branchId, int $daysBack = 30): array {
        $db = Database::getInstance();
        
        $hourlyData = $db->fetchAll(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count
             FROM tokens
             WHERE branch_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY HOUR(created_at)
             ORDER BY hour",
            [$branchId, $daysBack]
        );
        
        $totalTokens = array_sum(array_column($hourlyData, 'count'));
        $avgPerHour = $totalTokens / max(1, count($hourlyData));
        
        $hours = [];
        foreach ($hourlyData as $row) {
            $isPeak = $row['count'] > ($avgPerHour * 1.3);
            $hours[] = [
                'hour' => (int)$row['hour'],
                'label' => date('g A', mktime($row['hour'])),
                'count' => (int)$row['count'],
                'daily_avg' => round($row['count'] / $daysBack, 1),
                'is_peak' => $isPeak
            ];
        }
        
        return $hours;
    }
    
    /**
     * Calculate counter efficiency
     */
    public static function calculateCounterEfficiency(int $counterId, ?string $date = null): array {
        $db = Database::getInstance();
        $date = $date ?? date('Y-m-d');
        
        $totalServed = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens WHERE counter_id = ? AND date = ? AND status = 'completed'",
            [$counterId, $date]
        );
        
        $avgServiceTime = $db->fetchColumn(
            "SELECT AVG(actual_service_minutes) FROM tokens 
             WHERE counter_id = ? AND date = ? AND status = 'completed' AND actual_service_minutes > 0",
            [$counterId, $date]
        ) ?: 0;
        
        $totalServiceMinutes = $db->fetchColumn(
            "SELECT SUM(actual_service_minutes) FROM tokens 
             WHERE counter_id = ? AND date = ? AND status = 'completed' AND actual_service_minutes > 0",
            [$counterId, $date]
        ) ?: 0;
        
        // Calculate utilization (service time / shift hours)
        $shiftHours = 8; // Default
        $shiftMinutes = $shiftHours * 60;
        $utilization = $shiftMinutes > 0 ? min(100, round(($totalServiceMinutes / $shiftMinutes) * 100, 1)) : 0;
        
        return [
            'counter_id' => $counterId,
            'date' => $date,
            'total_served' => (int)$totalServed,
            'avg_service_time' => round($avgServiceTime, 1),
            'total_service_minutes' => (int)$totalServiceMinutes,
            'utilization_pct' => $utilization,
            'idle_minutes' => max(0, $shiftMinutes - $totalServiceMinutes)
        ];
    }
    
    /**
     * Recommend best counter for a service
     */
    public static function recommendCounter(int $serviceId, int $branchId): ?array {
        $db = Database::getInstance();
        
        // Get the department for this service
        $departmentId = $db->fetchColumn(
            "SELECT department_id FROM services WHERE id = ?",
            [$serviceId]
        );
        
        if (!$departmentId) return null;
        
        // Get open counters assigned to this department
        $counters = $db->fetchAll(
            "SELECT c.id, c.number, c.name,
                    (SELECT COUNT(*) FROM tokens t WHERE t.department_id = ? AND t.date = CURDATE() AND t.status = 'waiting') as queue_length,
                    (SELECT AVG(actual_service_minutes) FROM tokens t WHERE t.counter_id = c.id AND t.status = 'completed' AND t.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as avg_time
             FROM counters c
             JOIN staff_assignments sa ON sa.counter_id = c.id
             WHERE sa.department_id = ? AND c.status = 'open' AND sa.is_online = 1 AND c.branch_id = ?
             ORDER BY queue_length ASC, avg_time ASC
             LIMIT 1",
            [$departmentId, $departmentId, $branchId]
        );
        
        return !empty($counters) ? $counters[0] : null;
    }
    
    /**
     * Calculate SLA compliance
     */
    public static function calculateSLA(int $serviceId, ?string $dateFrom = null, ?string $dateTo = null): array {
        $db = Database::getInstance();
        
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $slaRule = $db->fetchOne(
            "SELECT * FROM sla_rules WHERE service_id = ?",
            [$serviceId]
        );
        
        $maxWait = $slaRule['max_wait_minutes'] ?? 30;
        $maxService = $slaRule['max_service_minutes'] ?? 20;
        
        $totalCompleted = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens 
             WHERE service_id = ? AND date BETWEEN ? AND ? AND status = 'completed'",
            [$serviceId, $dateFrom, $dateTo]
        );
        
        $withinWaitSLA = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens 
             WHERE service_id = ? AND date BETWEEN ? AND ? AND status = 'completed'
             AND actual_wait_minutes <= ?",
            [$serviceId, $dateFrom, $dateTo, $maxWait]
        );
        
        $withinServiceSLA = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens 
             WHERE service_id = ? AND date BETWEEN ? AND ? AND status = 'completed'
             AND actual_service_minutes <= ?",
            [$serviceId, $dateFrom, $dateTo, $maxService]
        );
        
        $waitCompliance = $totalCompleted > 0 ? round(($withinWaitSLA / $totalCompleted) * 100, 1) : 100;
        $serviceCompliance = $totalCompleted > 0 ? round(($withinServiceSLA / $totalCompleted) * 100, 1) : 100;
        
        return [
            'service_id' => $serviceId,
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_completed' => (int)$totalCompleted,
            'wait_sla' => ['threshold' => $maxWait, 'met' => (int)$withinWaitSLA, 'compliance_pct' => $waitCompliance],
            'service_sla' => ['threshold' => $maxService, 'met' => (int)$withinServiceSLA, 'compliance_pct' => $serviceCompliance],
            'overall_compliance' => round(($waitCompliance + $serviceCompliance) / 2, 1)
        ];
    }
    
    /**
     * Get AI-style insights for a branch
     */
    public static function getInsights(int $branchId): array {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $insights = [];
        
        // Check for understaffed departments
        $departments = $db->fetchAll(
            "SELECT d.id, d.name,
                    (SELECT COUNT(*) FROM tokens t WHERE t.department_id = d.id AND t.date = ? AND t.status = 'waiting') as waiting,
                    (SELECT COUNT(DISTINCT c.id) FROM counters c JOIN staff_assignments sa ON sa.counter_id = c.id WHERE sa.department_id = d.id AND c.status = 'open' AND sa.is_online = 1) as active_counters
             FROM departments d WHERE d.branch_id = ? AND d.is_active = 1",
            [$today, $branchId]
        );
        
        foreach ($departments as $dept) {
            if ($dept['active_counters'] == 0 && $dept['waiting'] > 0) {
                $insights[] = [
                    'type' => 'critical',
                    'icon' => 'bi-exclamation-triangle',
                    'title' => 'No Active Counters',
                    'message' => "{$dept['name']} has {$dept['waiting']} waiting tokens but no active counters.",
                    'action' => 'Assign staff immediately'
                ];
            } elseif ($dept['active_counters'] > 0 && ($dept['waiting'] / $dept['active_counters']) > 10) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'bi-people',
                    'title' => 'High Queue Load',
                    'message' => "{$dept['name']} has {$dept['waiting']} waiting with only {$dept['active_counters']} counters.",
                    'action' => 'Consider opening more counters'
                ];
            }
        }
        
        // Check SLA compliance
        $avgWait = $db->fetchColumn(
            "SELECT AVG(actual_wait_minutes) FROM tokens 
             WHERE branch_id = ? AND date = ? AND status = 'completed' AND actual_wait_minutes IS NOT NULL",
            [$branchId, $today]
        );
        
        if ($avgWait && $avgWait > 25) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'bi-clock',
                'title' => 'High Average Wait Time',
                'message' => "Average wait time is " . round($avgWait, 0) . " minutes, above the 25-minute threshold.",
                'action' => 'Review counter allocation'
            ];
        }
        
        // Peak hour approaching
        $currentHour = (int)date('H');
        $peakHours = self::detectPeakHours($branchId, 14);
        $nextPeak = null;
        foreach ($peakHours as $ph) {
            if ($ph['is_peak'] && $ph['hour'] > $currentHour && $ph['hour'] <= $currentHour + 2) {
                $nextPeak = $ph;
                break;
            }
        }
        
        if ($nextPeak) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'bi-graph-up',
                'title' => 'Peak Hour Approaching',
                'message' => "Expected high demand at {$nextPeak['label']} (~{$nextPeak['daily_avg']} tokens/day).",
                'action' => 'Ensure adequate staffing'
            ];
        }
        
        // Positive insight
        $satisfaction = $db->fetchColumn(
            "SELECT AVG(rating) FROM feedback WHERE branch_id = ? AND DATE(created_at) = ?",
            [$branchId, $today]
        );
        
        if ($satisfaction && $satisfaction >= 4.5) {
            $insights[] = [
                'type' => 'success',
                'icon' => 'bi-star',
                'title' => 'Excellent Customer Satisfaction',
                'message' => "Today's average rating is " . round($satisfaction, 1) . "/5.",
                'action' => 'Keep up the great work!'
            ];
        }
        
        return $insights;
    }
    
    /**
     * Get peak hour factor for wait estimation
     */
    private static function getPeakFactor(int $branchId): float {
        $currentHour = (int)date('H');
        
        // Peak hours typically 10-12 and 14-16
        if (($currentHour >= 10 && $currentHour <= 12) || ($currentHour >= 14 && $currentHour <= 16)) {
            return 1.3;
        }
        
        return 1.0;
    }
    
    /**
     * Get dashboard stats for admin
     */
    public static function getAdminDashboardStats(int $branchId): array {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        
        $stats = [
            'total_tokens_today' => $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens WHERE branch_id = ? AND date = ?",
                [$branchId, $today]
            ),
            'active_tokens' => $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens WHERE branch_id = ? AND date = ? AND status IN ('waiting', 'serving')",
                [$branchId, $today]
            ),
            'completed_tokens' => $db->fetchColumn(
                "SELECT COUNT(*) FROM tokens WHERE branch_id = ? AND date = ? AND status = 'completed'",
                [$branchId, $today]
            ),
            'avg_wait_time' => round($db->fetchColumn(
                "SELECT AVG(actual_wait_minutes) FROM tokens WHERE branch_id = ? AND date = ? AND status = 'completed' AND actual_wait_minutes IS NOT NULL",
                [$branchId, $today]
            ) ?: 0, 1),
            'avg_service_time' => round($db->fetchColumn(
                "SELECT AVG(actual_service_minutes) FROM tokens WHERE branch_id = ? AND date = ? AND status = 'completed' AND actual_service_minutes IS NOT NULL",
                [$branchId, $today]
            ) ?: 0, 1),
            'customer_satisfaction' => round($db->fetchColumn(
                "SELECT AVG(rating) FROM feedback WHERE branch_id = ? AND DATE(created_at) >= DATE_SUB(?, INTERVAL 30 DAY)",
                [$branchId, $today]
            ) ?: 0, 1),
            'active_counters' => $db->fetchColumn(
                "SELECT COUNT(*) FROM counters WHERE branch_id = ? AND status = 'open'",
                [$branchId]
            ),
            'total_staff_online' => $db->fetchColumn(
                "SELECT COUNT(*) FROM staff_assignments WHERE branch_id = ? AND is_online = 1",
                [$branchId]
            )
        ];
        
        // SLA compliance (last 30 days)
        $totalCompleted30 = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens WHERE branch_id = ? AND date >= DATE_SUB(?, INTERVAL 30 DAY) AND status = 'completed'",
            [$branchId, $today]
        );
        $withinSLA30 = $db->fetchColumn(
            "SELECT COUNT(*) FROM tokens t
             JOIN sla_rules sr ON sr.service_id = t.service_id
             WHERE t.branch_id = ? AND t.date >= DATE_SUB(?, INTERVAL 30 DAY) AND t.status = 'completed'
             AND t.actual_wait_minutes <= sr.max_wait_minutes",
            [$branchId, $today]
        );
        $stats['sla_compliance'] = $totalCompleted30 > 0 ? round(($withinSLA30 / $totalCompleted30) * 100, 1) : 100;
        
        // Hourly token distribution for today
        $stats['hourly_tokens'] = $db->fetchAll(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count
             FROM tokens WHERE branch_id = ? AND date = ?
             GROUP BY HOUR(created_at) ORDER BY hour",
            [$branchId, $today]
        );
        
        return $stats;
    }
}

<?php
/**
 * TokenFlow Pro — JSON API Response Helper
 */

class Response {
    
    /**
     * Send success response
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send error response
     */
    public static function error(string $message = 'An error occurred', int $statusCode = 400, $errors = null): void {
        $payload = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        
        self::send($payload, $statusCode);
    }
    
    /**
     * Send paginated response
     */
    public static function paginated(array $items, int $total, int $page, int $perPage, string $message = 'Success'): void {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
                'has_next' => ($page * $perPage) < $total,
                'has_prev' => $page > 1
            ]
        ]);
    }
    
    /**
     * Send validation error
     */
    public static function validationError(array $errors): void {
        self::send([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $errors
        ], 422);
    }
    
    /**
     * Send 401 Unauthorized
     */
    public static function unauthorized(string $message = 'Authentication required.'): void {
        self::error($message, 401);
    }
    
    /**
     * Send 403 Forbidden
     */
    public static function forbidden(string $message = 'Access denied.'): void {
        self::error($message, 403);
    }
    
    /**
     * Send 404 Not Found
     */
    public static function notFound(string $message = 'Resource not found.'): void {
        self::error($message, 404);
    }
    
    /**
     * Send raw JSON response
     */
    private static function send(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

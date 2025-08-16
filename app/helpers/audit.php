<?php
if(session_status() === PHP_SESSION_NONE) session_start();
function audit_log(PDO $pdo, int $firmId, string $entityType, int $entityId, string $action, ?array $old, ?array $new): void {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (firm_id,user_id,entity_type,entity_id,action,old_values,new_values,ip,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
    $stmt->execute([
        $firmId,
        $_SESSION['user']['id'] ?? null,
        $entityType,
        $entityId,
        $action,
        $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
        $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}
?>

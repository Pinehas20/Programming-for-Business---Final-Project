<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['full_name'] ?? 'User';
}

function getUnreadInboxCount() {
    if (!isLoggedIn()) return 0;
    
    try {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inbox WHERE recipient_id = :user_id AND is_read = 0");
        $stmt->execute([':user_id' => getCurrentUserId()]);
        return $stmt->fetch()['count'];
    } catch (Exception $e) {
        return 0;
    }
}
?>

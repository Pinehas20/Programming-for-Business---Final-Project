<?php
$pageTitle = 'Inbox - HRMS';
require_once 'config/database.php';
require_once 'includes/session.php';

if (getenv('PGHOST')) {
    require_once 'config/init_db.php';
    initializeDatabase();
}

requireLogin();

$database = new Database();
$conn = $database->getConnection();
$isPostgres = getenv('PGHOST') ? true : false;

$userId = getCurrentUserId();

if (isset($_GET['read'])) {
    $messageId = intval($_GET['read']);
    $stmt = $conn->prepare("UPDATE inbox SET is_read = 1 WHERE id = :id AND recipient_id = :user_id");
    $stmt->execute([':id' => $messageId, ':user_id' => $userId]);
}

if (isset($_GET['delete'])) {
    $messageId = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM inbox WHERE id = :id AND recipient_id = :user_id");
    $stmt->execute([':id' => $messageId, ':user_id' => $userId]);
    header('Location: inbox.php');
    exit();
}

$viewMessage = null;
if (isset($_GET['view'])) {
    $messageId = intval($_GET['view']);
    $stmtView = $conn->prepare("
        SELECT i.*, u.full_name as sender_full_name 
        FROM inbox i 
        LEFT JOIN users u ON i.sender_id = u.id 
        WHERE i.id = :id AND i.recipient_id = :user_id
    ");
    $stmtView->execute([':id' => $messageId, ':user_id' => $userId]);
    $viewMessage = $stmtView->fetch();
    
    if ($viewMessage && !$viewMessage['is_read']) {
        $conn->prepare("UPDATE inbox SET is_read = 1 WHERE id = :id")->execute([':id' => $messageId]);
    }
}

$filter = $_GET['filter'] ?? 'all';
$whereClause = "WHERE recipient_id = :user_id";
if ($filter === 'unread') {
    $whereClause .= " AND is_read = 0";
} elseif ($filter === 'important') {
    $whereClause .= " AND is_important = 1";
}

$stmtMessages = $conn->prepare("
    SELECT i.*, u.full_name as sender_full_name 
    FROM inbox i 
    LEFT JOIN users u ON i.sender_id = u.id 
    $whereClause
    ORDER BY i.created_at DESC
");
$stmtMessages->execute([':user_id' => $userId]);
$messages = $stmtMessages->fetchAll();

$stmtCounts = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread,
        COUNT(CASE WHEN is_important = 1 THEN 1 END) as important
    FROM inbox WHERE recipient_id = :user_id
");
$stmtCounts->execute([':user_id' => $userId]);
$counts = $stmtCounts->fetch();

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-envelope me-2"></i>Inbox</h5>
                    
                    <div class="list-group list-group-flush">
                        <a href="?filter=all" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            <span><i class="bi bi-inbox me-2"></i>Semua Pesan</span>
                            <span class="badge bg-secondary rounded-pill"><?php echo $counts['total']; ?></span>
                        </a>
                        <a href="?filter=unread" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                            <span><i class="bi bi-envelope me-2"></i>Belum Dibaca</span>
                            <span class="badge bg-danger rounded-pill"><?php echo $counts['unread']; ?></span>
                        </a>
                        <a href="?filter=important" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $filter === 'important' ? 'active' : ''; ?>">
                            <span><i class="bi bi-star me-2"></i>Penting</span>
                            <span class="badge bg-warning rounded-pill"><?php echo $counts['important']; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <?php if ($viewMessage): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <a href="inbox.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                    <div>
                        <a href="?delete=<?php echo $viewMessage['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pesan ini?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h4><?php echo htmlspecialchars($viewMessage['subject']); ?></h4>
                        <div class="d-flex justify-content-between align-items-center text-muted">
                            <div>
                                <strong>Dari:</strong> <?php echo htmlspecialchars($viewMessage['sender_name'] ?? $viewMessage['sender_full_name'] ?? 'System'); ?>
                            </div>
                            <div>
                                <?php echo date('d F Y, H:i', strtotime($viewMessage['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="message-content">
                        <?php echo nl2br(htmlspecialchars($viewMessage['message'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php 
                        if ($filter === 'unread') echo '<i class="bi bi-envelope me-2"></i>Pesan Belum Dibaca';
                        elseif ($filter === 'important') echo '<i class="bi bi-star me-2"></i>Pesan Penting';
                        else echo '<i class="bi bi-inbox me-2"></i>Semua Pesan';
                        ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state py-5">
                            <i class="bi bi-envelope-open"></i>
                            <h5>Tidak Ada Pesan</h5>
                            <p>Inbox Anda kosong.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($messages as $msg): ?>
                                <a href="?view=<?php echo $msg['id']; ?>" class="list-group-item list-group-item-action <?php echo !$msg['is_read'] ? 'bg-light' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div class="d-flex align-items-start">
                                            <?php if (!$msg['is_read']): ?>
                                                <span class="badge bg-primary me-2 mt-1">Baru</span>
                                            <?php endif; ?>
                                            <?php if ($msg['is_important']): ?>
                                                <i class="bi bi-star-fill text-warning me-2 mt-1"></i>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-1 <?php echo !$msg['is_read'] ? 'fw-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($msg['subject']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    Dari: <?php echo htmlspecialchars($msg['sender_name'] ?? $msg['sender_full_name'] ?? 'System'); ?>
                                                </small>
                                                <p class="mb-0 text-muted small">
                                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 80)) . (strlen($msg['message']) > 80 ? '...' : ''); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <small class="text-muted text-nowrap">
                                            <?php 
                                            $msgDate = strtotime($msg['created_at']);
                                            if (date('Y-m-d') === date('Y-m-d', $msgDate)) {
                                                echo date('H:i', $msgDate);
                                            } elseif (date('Y') === date('Y', $msgDate)) {
                                                echo date('d M', $msgDate);
                                            } else {
                                                echo date('d/m/Y', $msgDate);
                                            }
                                            ?>
                                        </small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

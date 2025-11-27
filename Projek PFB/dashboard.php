<?php
$pageTitle = 'Dashboard - HRMS';
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

$database = new Database();
$conn = $database->getConnection();

$currentMonth = date('Y-m');
$userId = getCurrentUserId();
$isAdminUser = isAdmin();

if ($isAdminUser) {
    $stmtTotal = $conn->prepare("
        SELECT COALESCE(SUM(duration), 0) as total_minutes 
        FROM overtime 
        WHERE TO_CHAR(date, 'YYYY-MM') = :month AND status = 'APPROVED'
    ");
    $stmtTotal->execute([':month' => $currentMonth]);
    
    $stmtPending = $conn->prepare("SELECT COUNT(*) as count FROM overtime WHERE status = 'PENDING'");
    $stmtPending->execute();
    
    $stmtApproved = $conn->prepare("
        SELECT COUNT(*) as count FROM overtime 
        WHERE TO_CHAR(date, 'YYYY-MM') = :month AND status = 'APPROVED'
    ");
    $stmtApproved->execute([':month' => $currentMonth]);
    
    $stmtRecent = $conn->prepare("
        SELECT o.*, u.full_name 
        FROM overtime o 
        JOIN users u ON o.employee_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmtRecent->execute();
} else {
    $stmtTotal = $conn->prepare("
        SELECT COALESCE(SUM(duration), 0) as total_minutes 
        FROM overtime 
        WHERE employee_id = :user_id AND TO_CHAR(date, 'YYYY-MM') = :month AND status = 'APPROVED'
    ");
    $stmtTotal->execute([':user_id' => $userId, ':month' => $currentMonth]);
    
    $stmtPending = $conn->prepare("SELECT COUNT(*) as count FROM overtime WHERE employee_id = :user_id AND status = 'PENDING'");
    $stmtPending->execute([':user_id' => $userId]);
    
    $stmtApproved = $conn->prepare("
        SELECT COUNT(*) as count FROM overtime 
        WHERE employee_id = :user_id AND TO_CHAR(date, 'YYYY-MM') = :month AND status = 'APPROVED'
    ");
    $stmtApproved->execute([':user_id' => $userId, ':month' => $currentMonth]);
    
    $stmtRecent = $conn->prepare("
        SELECT o.*, u.full_name 
        FROM overtime o 
        JOIN users u ON o.employee_id = u.id 
        WHERE o.employee_id = :user_id
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmtRecent->execute([':user_id' => $userId]);
}

$totalMinutes = $stmtTotal->fetch()['total_minutes'];
$totalHours = floor($totalMinutes / 60);
$remainingMins = $totalMinutes % 60;

$pendingCount = $stmtPending->fetch()['count'];
$approvedCount = $stmtApproved->fetch()['count'];
$recentOvertime = $stmtRecent->fetchAll();

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p class="text-muted">Selamat datang, <?php echo htmlspecialchars(getCurrentUserName()); ?>!</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Lembur Bulan Ini</h6>
                        <div class="stat-number text-success"><?php echo $totalHours; ?>j <?php echo $remainingMins; ?>m</div>
                        <small class="text-muted"><?php echo $totalMinutes; ?> menit</small>
                    </div>
                    <i class="bi bi-clock stat-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card warning h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1"><?php echo $isAdminUser ? 'Menunggu Persetujuan' : 'Pengajuan Pending'; ?></h6>
                        <div class="stat-number text-warning"><?php echo $pendingCount; ?></div>
                        <small class="text-muted">pengajuan</small>
                    </div>
                    <i class="bi bi-hourglass-split stat-icon text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Lembur Disetujui Bulan Ini</h6>
                        <div class="stat-number text-primary"><?php echo $approvedCount; ?></div>
                        <small class="text-muted">pengajuan</small>
                    </div>
                    <i class="bi bi-check-circle stat-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Riwayat Lembur Terbaru</h5>
                    <?php if ($isAdminUser): ?>
                        <a href="overtime_admin.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                    <?php else: ?>
                        <a href="overtime.php" class="btn btn-sm btn-primary">Ajukan Lembur</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOvertime)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>Belum Ada Data Lembur</h5>
                            <p>Data lembur akan muncul di sini setelah ada pengajuan.</p>
                            <?php if (!$isAdminUser): ?>
                                <a href="overtime.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Ajukan Lembur
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <?php if ($isAdminUser): ?>
                                            <th>Karyawan</th>
                                        <?php endif; ?>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Durasi</th>
                                        <th>Alasan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOvertime as $ot): ?>
                                        <tr>
                                            <?php if ($isAdminUser): ?>
                                                <td><strong><?php echo htmlspecialchars($ot['full_name']); ?></strong></td>
                                            <?php endif; ?>
                                            <td><?php echo date('d M Y', strtotime($ot['date'])); ?></td>
                                            <td>
                                                <?php echo date('H:i', strtotime($ot['start_time'])); ?> - 
                                                <?php echo date('H:i', strtotime($ot['end_time'])); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $hours = floor($ot['duration'] / 60);
                                                    $mins = $ot['duration'] % 60;
                                                    echo "{$hours}j {$mins}m";
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($ot['reason'], 0, 50)) . (strlen($ot['reason']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <?php
                                                    $badgeClass = 'badge-pending';
                                                    if ($ot['status'] === 'APPROVED') $badgeClass = 'badge-approved';
                                                    if ($ot['status'] === 'REJECTED') $badgeClass = 'badge-rejected';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $ot['status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

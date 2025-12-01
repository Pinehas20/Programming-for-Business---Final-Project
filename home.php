<?php
$pageTitle = 'Home - HRMS';
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
$currentMonth = date('n');
$currentYear = date('Y');

$stmtKPI = $conn->prepare("
    SELECT category, target_value, actual_value 
    FROM kpi 
    WHERE employee_id = :user_id AND period_month = :month AND period_year = :year
");
$stmtKPI->execute([':user_id' => $userId, ':month' => $currentMonth, ':year' => $currentYear]);
$kpiData = $stmtKPI->fetchAll();

$stmtFeeds = $conn->prepare("
    SELECT cf.*, u.full_name as author_name 
    FROM company_feeds cf 
    LEFT JOIN users u ON cf.author_id = u.id 
    ORDER BY cf.is_pinned DESC, cf.created_at DESC 
    LIMIT 5
");
$stmtFeeds->execute();
$feeds = $stmtFeeds->fetchAll();

$stmtAttendance = $conn->prepare("
    SELECT * FROM attendance 
    WHERE employee_id = :user_id AND date = CURRENT_DATE
");
$stmtAttendance->execute([':user_id' => $userId]);
$todayAttendance = $stmtAttendance->fetch();

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="page-header mb-4">
                <h1><i class="bi bi-house me-2"></i>Home</h1>
                <p class="text-muted">Selamat datang, <?php echo htmlspecialchars(getCurrentUserName()); ?>!</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="attendance.php" class="text-decoration-none">
                        <div class="card shortcut-card h-100 text-center p-3">
                            <i class="bi bi-calendar-check text-primary" style="font-size: 2.5rem;"></i>
                            <h6 class="mt-2 mb-0">Kehadiran</h6>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="overtime.php" class="text-decoration-none">
                        <div class="card shortcut-card h-100 text-center p-3">
                            <i class="bi bi-clock text-warning" style="font-size: 2.5rem;"></i>
                            <h6 class="mt-2 mb-0">Lembur</h6>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="leave.php" class="text-decoration-none">
                        <div class="card shortcut-card h-100 text-center p-3">
                            <i class="bi bi-calendar-x text-danger" style="font-size: 2.5rem;"></i>
                            <h6 class="mt-2 mb-0">Cuti</h6>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="payslip.php" class="text-decoration-none">
                        <div class="card shortcut-card h-100 text-center p-3">
                            <i class="bi bi-wallet2 text-success" style="font-size: 2.5rem;"></i>
                            <h6 class="mt-2 mb-0">Slip Gaji</h6>
                        </div>
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>KPI Progress - <?php echo date('F Y'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($kpiData)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-bar-chart" style="font-size: 3rem;"></i>
                            <p class="mt-2">Belum ada data KPI untuk bulan ini</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="kpiChart" height="250"></canvas>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Detail KPI</h6>
                                <?php foreach ($kpiData as $kpi): 
                                    $percentage = ($kpi['actual_value'] / $kpi['target_value']) * 100;
                                    $progressClass = $percentage >= 90 ? 'bg-success' : ($percentage >= 70 ? 'bg-warning' : 'bg-danger');
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><?php echo htmlspecialchars($kpi['category']); ?></span>
                                        <span class="fw-bold"><?php echo number_format($percentage, 1); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar <?php echo $progressClass; ?>" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                    </div>
                                    <small class="text-muted">Actual: <?php echo $kpi['actual_value']; ?> / Target: <?php echo $kpi['target_value']; ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-megaphone me-2"></i>Company Feed</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($feeds)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-newspaper" style="font-size: 3rem;"></i>
                            <p class="mt-2">Belum ada pengumuman</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feeds as $feed): ?>
                        <div class="feed-item mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <?php if ($feed['is_pinned']): ?>
                                        <span class="badge bg-danger me-2"><i class="bi bi-pin-angle"></i> Pinned</span>
                                    <?php endif; ?>
                                    <span class="badge bg-<?php 
                                        echo $feed['feed_type'] === 'announcement' ? 'primary' : 
                                            ($feed['feed_type'] === 'policy' ? 'info' : 'success'); 
                                    ?>"><?php echo ucfirst($feed['feed_type']); ?></span>
                                </div>
                                <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($feed['created_at'])); ?></small>
                            </div>
                            <h6 class="mt-2 mb-1"><?php echo htmlspecialchars($feed['title']); ?></h6>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($feed['content']); ?></p>
                            <small class="text-muted">Posted by <?php echo htmlspecialchars($feed['author_name'] ?? 'System'); ?></small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Kehadiran Hari Ini</h5>
                </div>
                <div class="card-body">
                    <?php if ($todayAttendance): ?>
                        <div class="text-center mb-3">
                            <span class="badge bg-<?php echo $todayAttendance['work_type'] === 'WFO' ? 'primary' : 'success'; ?> fs-6">
                                <?php echo $todayAttendance['work_type']; ?>
                            </span>
                        </div>
                        <div class="row text-center">
                            <div class="col-6">
                                <h6 class="text-muted mb-1">Check In</h6>
                                <h4 class="text-success"><?php echo $todayAttendance['check_in'] ? date('H:i', strtotime($todayAttendance['check_in'])) : '-'; ?></h4>
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted mb-1">Check Out</h6>
                                <h4 class="text-danger"><?php echo $todayAttendance['check_out'] ? date('H:i', strtotime($todayAttendance['check_out'])) : '-'; ?></h4>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-clock text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2 mb-3">Anda belum check-in hari ini</p>
                            <a href="attendance.php" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Check In Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i><?php echo date('l, d F Y'); ?></h5>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-4 mb-0"><?php echo date('H:i'); ?></h1>
                    <p class="text-muted">Waktu Server</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Stats</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmtPending = $conn->prepare("SELECT COUNT(*) as count FROM overtime WHERE employee_id = :user_id AND status = 'pending'");
                    $stmtPending->execute([':user_id' => $userId]);
                    $pendingOT = $stmtPending->fetch()['count'];
                    
                    $stmtLeave = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = :user_id AND status = 'pending'");
                    $stmtLeave->execute([':user_id' => $userId]);
                    $pendingLeave = $stmtLeave->fetch()['count'];
                    
                    $stmtUnread = $conn->prepare("SELECT COUNT(*) as count FROM inbox WHERE recipient_id = :user_id AND is_read = FALSE");
                    $stmtUnread->execute([':user_id' => $userId]);
                    $unreadInbox = $stmtUnread->fetch()['count'];
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Pending Lembur</span>
                        <span class="badge bg-warning"><?php echo $pendingOT; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Pending Cuti</span>
                        <span class="badge bg-info"><?php echo $pendingLeave; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Pesan Belum Dibaca</span>
                        <span class="badge bg-danger"><?php echo $unreadInbox; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
<?php if (!empty($kpiData)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('kpiChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($kpiData, 'category')); ?>,
            datasets: [{
                label: 'Target',
                data: <?php echo json_encode(array_column($kpiData, 'target_value')); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.5)',
                borderColor: 'rgb(13, 110, 253)',
                borderWidth: 1
            }, {
                label: 'Actual',
                data: <?php echo json_encode(array_column($kpiData, 'actual_value')); ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.5)',
                borderColor: 'rgb(25, 135, 84)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'KPI Comparison: Target vs Actual'
                }
            }
        }
    });
});
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>

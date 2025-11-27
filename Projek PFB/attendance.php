<?php
$pageTitle = 'Attendance - HRMS';
require_once 'config/database.php';
require_once 'config/init_db.php';
require_once 'includes/session.php';

initializeDatabase();
requireLogin();

$database = new Database();
$conn = $database->getConnection();

$userId = getCurrentUserId();
$success = '';
$error = '';

$stmtToday = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :user_id AND date = CURRENT_DATE");
$stmtToday->execute([':user_id' => $userId]);
$todayAttendance = $stmtToday->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'checkin') {
        $workType = $_POST['work_type'] ?? 'WFO';
        $location = trim($_POST['location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($todayAttendance) {
            $error = 'Anda sudah melakukan check-in hari ini!';
        } else {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO attendance (employee_id, date, check_in, work_type, location, notes, status) 
                    VALUES (:emp_id, CURRENT_DATE, CURRENT_TIMESTAMP, :work_type, :location, :notes, 'PRESENT')
                ");
                $stmt->execute([
                    ':emp_id' => $userId,
                    ':work_type' => $workType,
                    ':location' => $location,
                    ':notes' => $notes
                ]);
                $success = 'Check-in berhasil! Selamat bekerja.';
                
                $stmtToday->execute([':user_id' => $userId]);
                $todayAttendance = $stmtToday->fetch();
            } catch (PDOException $e) {
                error_log("Check-in error: " . $e->getMessage());
                $error = 'Terjadi kesalahan saat check-in.';
            }
        }
    } elseif ($action === 'checkout') {
        if (!$todayAttendance) {
            $error = 'Anda belum melakukan check-in hari ini!';
        } elseif ($todayAttendance['check_out']) {
            $error = 'Anda sudah melakukan check-out hari ini!';
        } else {
            try {
                $stmt = $conn->prepare("UPDATE attendance SET check_out = CURRENT_TIMESTAMP WHERE id = :id");
                $stmt->execute([':id' => $todayAttendance['id']]);
                $success = 'Check-out berhasil! Sampai jumpa besok.';
                
                $stmtToday->execute([':user_id' => $userId]);
                $todayAttendance = $stmtToday->fetch();
            } catch (PDOException $e) {
                error_log("Check-out error: " . $e->getMessage());
                $error = 'Terjadi kesalahan saat check-out.';
            }
        }
    }
}

$filterMonth = $_GET['month'] ?? date('Y-m');
$stmtHistory = $conn->prepare("
    SELECT * FROM attendance 
    WHERE employee_id = :user_id AND TO_CHAR(date, 'YYYY-MM') = :month
    ORDER BY date DESC
");
$stmtHistory->execute([':user_id' => $userId, ':month' => $filterMonth]);
$attendanceHistory = $stmtHistory->fetchAll();

$stmtStats = $conn->prepare("
    SELECT 
        COUNT(*) as total_days,
        COUNT(CASE WHEN work_type = 'WFO' THEN 1 END) as wfo_days,
        COUNT(CASE WHEN work_type = 'WFH' THEN 1 END) as wfh_days
    FROM attendance 
    WHERE employee_id = :user_id AND TO_CHAR(date, 'YYYY-MM') = :month
");
$stmtStats->execute([':user_id' => $userId, ':month' => $filterMonth]);
$stats = $stmtStats->fetch();

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-calendar-check me-2"></i>Attendance</h1>
        <p class="text-muted">Kelola kehadiran Anda</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Kehadiran Hari Ini - <?php echo date('d F Y'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!$todayAttendance): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="checkin">
                            
                            <div class="mb-3">
                                <label class="form-label">Tipe Kerja</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="work_type" id="wfo" value="WFO" checked>
                                    <label class="btn btn-outline-primary" for="wfo">
                                        <i class="bi bi-building me-1"></i>WFO (Office)
                                    </label>
                                    <input type="radio" class="btn-check" name="work_type" id="wfh" value="WFH">
                                    <label class="btn btn-outline-success" for="wfh">
                                        <i class="bi bi-house me-1"></i>WFH (Home)
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Lokasi (opsional)</label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="Contoh: Kantor Pusat Jakarta">
                            </div>

                            <div class="mb-4">
                                <label for="notes" class="form-label">Catatan (opsional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Catatan tambahan..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success w-100 btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Check In
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center mb-4">
                            <span class="badge bg-<?php echo $todayAttendance['work_type'] === 'WFO' ? 'primary' : 'success'; ?> fs-5 px-4 py-2">
                                <i class="bi bi-<?php echo $todayAttendance['work_type'] === 'WFO' ? 'building' : 'house'; ?> me-2"></i>
                                <?php echo $todayAttendance['work_type']; ?>
                            </span>
                        </div>

                        <div class="row text-center mb-4">
                            <div class="col-6">
                                <div class="p-3 bg-success bg-opacity-10 rounded">
                                    <h6 class="text-muted mb-1">Check In</h6>
                                    <h3 class="text-success mb-0">
                                        <?php echo date('H:i', strtotime($todayAttendance['check_in'])); ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-danger bg-opacity-10 rounded">
                                    <h6 class="text-muted mb-1">Check Out</h6>
                                    <h3 class="text-danger mb-0">
                                        <?php echo $todayAttendance['check_out'] ? date('H:i', strtotime($todayAttendance['check_out'])) : '-'; ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <?php if ($todayAttendance['location']): ?>
                            <p class="mb-2"><strong>Lokasi:</strong> <?php echo htmlspecialchars($todayAttendance['location']); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($todayAttendance['notes']): ?>
                            <p class="mb-3"><strong>Catatan:</strong> <?php echo htmlspecialchars($todayAttendance['notes']); ?></p>
                        <?php endif; ?>

                        <?php if (!$todayAttendance['check_out']): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="checkout">
                                <button type="submit" class="btn btn-danger w-100 btn-lg">
                                    <i class="bi bi-box-arrow-right me-2"></i>Check Out
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                Anda sudah check-in dan check-out hari ini. Sampai jumpa besok!
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Statistik Bulan Ini</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h3 class="text-primary mb-0"><?php echo $stats['total_days']; ?></h3>
                            <small class="text-muted">Total Hari</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-info mb-0"><?php echo $stats['wfo_days']; ?></h3>
                            <small class="text-muted">WFO</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-success mb-0"><?php echo $stats['wfh_days']; ?></h3>
                            <small class="text-muted">WFH</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Kehadiran</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="month" name="month" class="form-control form-control-sm" value="<?php echo $filterMonth; ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (empty($attendanceHistory)): ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h5>Tidak Ada Data</h5>
                            <p>Belum ada data kehadiran untuk periode ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Tipe</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Durasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceHistory as $att): 
                                        $duration = '';
                                        if ($att['check_in'] && $att['check_out']) {
                                            $diff = strtotime($att['check_out']) - strtotime($att['check_in']);
                                            $hours = floor($diff / 3600);
                                            $mins = floor(($diff % 3600) / 60);
                                            $duration = "{$hours}j {$mins}m";
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($att['date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $att['work_type'] === 'WFO' ? 'primary' : 'success'; ?>">
                                                    <?php echo $att['work_type']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $att['check_in'] ? date('H:i', strtotime($att['check_in'])) : '-'; ?></td>
                                            <td><?php echo $att['check_out'] ? date('H:i', strtotime($att['check_out'])) : '-'; ?></td>
                                            <td><?php echo $duration ?: '-'; ?></td>
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

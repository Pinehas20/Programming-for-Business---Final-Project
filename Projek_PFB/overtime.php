<?php
$pageTitle = 'Pengajuan Lembur - HRMS';
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

$database = new Database();
$conn = $database->getConnection();

$userId = getCurrentUserId();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($date) || empty($startTime) || empty($endTime) || empty($reason)) {
        $error = 'Semua field harus diisi!';
    } else {
        $startDateTime = $date . ' ' . $startTime . ':00';
        $endDateTime = $date . ' ' . $endTime . ':00';
        
        $start = strtotime($startDateTime);
        $end = strtotime($endDateTime);

        if ($end <= $start) {
            $error = 'Jam selesai harus lebih besar dari jam mulai!';
        } else {
            $checkDuplicate = $conn->prepare("
                SELECT COUNT(*) as count FROM overtime 
                WHERE employee_id = :user_id AND date = :date AND status != 'REJECTED'
            ");
            $checkDuplicate->execute([':user_id' => $userId, ':date' => $date]);
            
            if ($checkDuplicate->fetch()['count'] > 0) {
                $error = 'Anda sudah mengajukan lembur untuk tanggal ini!';
            } else {
                $duration = ($end - $start) / 60;

                try {
                    $stmt = $conn->prepare("
                        INSERT INTO overtime (employee_id, date, start_time, end_time, duration, reason, status, created_at, updated_at) 
                        VALUES (:employee_id, :date, :start_time, :end_time, :duration, :reason, 'PENDING', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([
                        ':employee_id' => $userId,
                        ':date' => $date,
                        ':start_time' => $startDateTime,
                        ':end_time' => $endDateTime,
                        ':duration' => $duration,
                        ':reason' => $reason
                    ]);

                    $success = 'Pengajuan lembur berhasil disubmit!';
                    $_POST = [];
                } catch (PDOException $e) {
                    error_log("Overtime submission error: " . $e->getMessage());
                    $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
                }
            }
        }
    }
}

$stmtHistory = $conn->prepare("
    SELECT * FROM overtime 
    WHERE employee_id = :user_id 
    ORDER BY created_at DESC
");
$stmtHistory->execute([':user_id' => $userId]);
$overtimeHistory = $stmtHistory->fetchAll();

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-plus-circle me-2"></i>Pengajuan Lembur</h1>
        <p class="text-muted">Ajukan lembur baru dan lihat riwayat pengajuan Anda</p>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="overtime-form">
                <h5 class="mb-4"><i class="bi bi-file-earmark-plus me-2"></i>Form Pengajuan Baru</h5>
                
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

                <form id="overtime_form" method="POST" action="">
                    <div class="mb-3">
                        <label for="date" class="form-label">
                            <i class="bi bi-calendar me-1"></i>Tanggal Lembur
                        </label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">
                                    <i class="bi bi-clock me-1"></i>Jam Mulai
                                </label>
                                <input type="time" class="form-control" id="start_time" name="start_time" 
                                       value="<?php echo htmlspecialchars($_POST['start_time'] ?? '17:00'); ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">
                                    <i class="bi bi-clock-fill me-1"></i>Jam Selesai
                                </label>
                                <input type="time" class="form-control" id="end_time" name="end_time" 
                                       value="<?php echo htmlspecialchars($_POST['end_time'] ?? '20:00'); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Durasi Lembur:</label>
                        <div id="duration_display" class="duration-display">-</div>
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="form-label">
                            <i class="bi bi-chat-left-text me-1"></i>Alasan Lembur
                        </label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" 
                                  placeholder="Jelaskan alasan lembur Anda..." required><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-2"></i>Submit Pengajuan
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Pengajuan Lembur</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($overtimeHistory)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>Belum Ada Riwayat</h5>
                            <p>Riwayat pengajuan lembur Anda akan muncul di sini.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Durasi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overtimeHistory as $ot): ?>
                                        <tr>
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

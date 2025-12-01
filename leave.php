<?php
$pageTitle = 'Leave Request - HRMS';
require_once 'config/database.php';
require_once 'includes/session.php';

if (getenv('PGHOST')) {
    require_once 'config/init_db.php';
    initializeDatabase();
}

requireLogin();

$database = new Database();
$conn = $database->getConnection();

$userId = getCurrentUserId();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveType = $_POST['leave_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($leaveType) || empty($startDate) || empty($endDate) || empty($reason)) {
        $error = 'Semua field harus diisi!';
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        $error = 'Tanggal selesai harus sama atau setelah tanggal mulai!';
    } else {
        $checkOverlap = $conn->prepare("
            SELECT COUNT(*) as count FROM leave_requests 
            WHERE employee_id = :user_id 
            AND status != 'REJECTED'
            AND ((start_date <= :end_date AND end_date >= :start_date))
        ");
        $checkOverlap->execute([':user_id' => $userId, ':start_date' => $startDate, ':end_date' => $endDate]);
        
        if ($checkOverlap->fetch()['count'] > 0) {
            $error = 'Anda sudah memiliki pengajuan cuti/izin untuk periode yang sama!';
        } else {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status) 
                    VALUES (:emp_id, :type, :start, :end, :reason, 'PENDING')
                ");
                $stmt->execute([
                    ':emp_id' => $userId,
                    ':type' => $leaveType,
                    ':start' => $startDate,
                    ':end' => $endDate,
                    ':reason' => $reason
                ]);
                $success = 'Pengajuan cuti/izin berhasil disubmit!';
                $_POST = [];
            } catch (PDOException $e) {
                error_log("Leave request error: " . $e->getMessage());
                $error = 'Terjadi kesalahan saat menyimpan data.';
            }
        }
    }
}

$stmtHistory = $conn->prepare("
    SELECT lr.*, u.full_name as approver_name 
    FROM leave_requests lr
    LEFT JOIN users u ON lr.approved_by = u.id
    WHERE lr.employee_id = :user_id 
    ORDER BY lr.created_at DESC
");
$stmtHistory->execute([':user_id' => $userId]);
$leaveHistory = $stmtHistory->fetchAll();

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-calendar-x me-2"></i>Leave Request</h1>
        <p class="text-muted">Ajukan cuti atau izin</p>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="overtime-form">
                <h5 class="mb-4"><i class="bi bi-file-earmark-plus me-2"></i>Form Pengajuan Cuti/Izin</h5>
                
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

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="leave_type" class="form-label">
                            <i class="bi bi-tag me-1"></i>Jenis Cuti/Izin
                        </label>
                        <select class="form-select" id="leave_type" name="leave_type" required>
                            <option value="">Pilih jenis...</option>
                            <option value="SICK" <?php echo ($_POST['leave_type'] ?? '') === 'SICK' ? 'selected' : ''; ?>>Sakit</option>
                            <option value="ANNUAL" <?php echo ($_POST['leave_type'] ?? '') === 'ANNUAL' ? 'selected' : ''; ?>>Cuti Tahunan</option>
                            <option value="PERSONAL" <?php echo ($_POST['leave_type'] ?? '') === 'PERSONAL' ? 'selected' : ''; ?>>Keperluan Pribadi</option>
                            <option value="MATERNITY" <?php echo ($_POST['leave_type'] ?? '') === 'MATERNITY' ? 'selected' : ''; ?>>Cuti Melahirkan</option>
                            <option value="MARRIAGE" <?php echo ($_POST['leave_type'] ?? '') === 'MARRIAGE' ? 'selected' : ''; ?>>Cuti Menikah</option>
                            <option value="BEREAVEMENT" <?php echo ($_POST['leave_type'] ?? '') === 'BEREAVEMENT' ? 'selected' : ''; ?>>Cuti Duka</option>
                            <option value="OTHER" <?php echo ($_POST['leave_type'] ?? '') === 'OTHER' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">
                                    <i class="bi bi-calendar me-1"></i>Tanggal Mulai
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">
                                    <i class="bi bi-calendar-check me-1"></i>Tanggal Selesai
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="form-label">
                            <i class="bi bi-chat-left-text me-1"></i>Alasan
                        </label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" 
                                  placeholder="Jelaskan alasan cuti/izin Anda..." required><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
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
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Pengajuan</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($leaveHistory)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>Belum Ada Riwayat</h5>
                            <p>Riwayat pengajuan cuti/izin Anda akan muncul di sini.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis</th>
                                        <th>Tanggal</th>
                                        <th>Durasi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaveHistory as $leave): 
                                        $leaveTypes = [
                                            'SICK' => 'Sakit',
                                            'ANNUAL' => 'Cuti Tahunan',
                                            'PERSONAL' => 'Keperluan Pribadi',
                                            'MATERNITY' => 'Cuti Melahirkan',
                                            'MARRIAGE' => 'Cuti Menikah',
                                            'BEREAVEMENT' => 'Cuti Duka',
                                            'OTHER' => 'Lainnya'
                                        ];
                                        $days = (strtotime($leave['end_date']) - strtotime($leave['start_date'])) / 86400 + 1;
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $leaveTypes[$leave['leave_type']] ?? $leave['leave_type']; ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($leave['reason'], 0, 30)) . (strlen($leave['reason']) > 30 ? '...' : ''); ?></small>
                                            </td>
                                            <td>
                                                <?php echo date('d M', strtotime($leave['start_date'])); ?> - 
                                                <?php echo date('d M Y', strtotime($leave['end_date'])); ?>
                                            </td>
                                            <td><?php echo $days; ?> hari</td>
                                            <td>
                                                <?php
                                                    $badgeClass = 'badge-pending';
                                                    if ($leave['status'] === 'APPROVED') $badgeClass = 'badge-approved';
                                                    if ($leave['status'] === 'REJECTED') $badgeClass = 'badge-rejected';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $leave['status']; ?></span>
                                                <?php if ($leave['approver_name']): ?>
                                                    <br><small class="text-muted">oleh <?php echo htmlspecialchars($leave['approver_name']); ?></small>
                                                <?php endif; ?>
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

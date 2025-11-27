<?php
$pageTitle = 'Kelola Cuti - HRMS';
require_once 'config/database.php';
require_once 'config/init_db.php';
require_once 'includes/session.php';

initializeDatabase();
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $leaveId = intval($_POST['leave_id'] ?? 0);
    $action = $_POST['action'];
    $adminId = getCurrentUserId();

    if ($leaveId > 0 && in_array($action, ['approve', 'reject'])) {
        $newStatus = ($action === 'approve') ? 'APPROVED' : 'REJECTED';
        
        try {
            $stmt = $conn->prepare("
                UPDATE leave_requests 
                SET status = :status, approved_by = :admin_id, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $newStatus,
                ':admin_id' => $adminId,
                ':id' => $leaveId
            ]);

            $success = 'Pengajuan cuti berhasil ' . ($action === 'approve' ? 'disetujui' : 'ditolak') . '!';
        } catch (PDOException $e) {
            error_log("Leave admin action error: " . $e->getMessage());
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

$filterStatus = $_GET['status'] ?? '';
$filterEmployee = $_GET['employee'] ?? '';

$whereConditions = [];
$params = [];

if ($filterEmployee) {
    $whereConditions[] = "u.full_name ILIKE :employee";
    $params[':employee'] = '%' . $filterEmployee . '%';
}
if ($filterStatus) {
    $whereConditions[] = "lr.status = :status";
    $params[':status'] = $filterStatus;
}

$whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "
    SELECT lr.*, u.full_name, u.username, u.department,
           (SELECT full_name FROM users WHERE id = lr.approved_by) as approver_name
    FROM leave_requests lr 
    JOIN users u ON lr.employee_id = u.id 
    $whereClause
    ORDER BY 
        CASE lr.status WHEN 'PENDING' THEN 0 ELSE 1 END,
        lr.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$leaveList = $stmt->fetchAll();

$leaveTypes = [
    'SICK' => 'Sakit',
    'ANNUAL' => 'Cuti Tahunan',
    'PERSONAL' => 'Keperluan Pribadi',
    'MATERNITY' => 'Cuti Melahirkan',
    'MARRIAGE' => 'Cuti Menikah',
    'BEREAVEMENT' => 'Cuti Duka',
    'OTHER' => 'Lainnya'
];

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-calendar-x me-2"></i>Kelola Pengajuan Cuti/Izin</h1>
        <p class="text-muted">Setujui atau tolak pengajuan cuti/izin karyawan</p>
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

    <div class="filter-section">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="employee" class="form-label">Nama Karyawan</label>
                <input type="text" class="form-control" id="employee" name="employee" 
                       placeholder="Cari nama..." value="<?php echo htmlspecialchars($filterEmployee); ?>">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="PENDING" <?php echo $filterStatus === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                    <option value="APPROVED" <?php echo $filterStatus === 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                    <option value="REJECTED" <?php echo $filterStatus === 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="leave_admin.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($leaveList)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>Tidak Ada Data</h5>
                    <p>Tidak ada pengajuan cuti yang sesuai dengan filter.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Karyawan</th>
                                <th>Jenis</th>
                                <th>Periode</th>
                                <th>Durasi</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveList as $leave): 
                                $days = (strtotime($leave['end_date']) - strtotime($leave['start_date'])) / 86400 + 1;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($leave['full_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($leave['department']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $leaveTypes[$leave['leave_type']] ?? $leave['leave_type']; ?></span>
                                    </td>
                                    <td>
                                        <?php echo date('d M', strtotime($leave['start_date'])); ?> - 
                                        <?php echo date('d M Y', strtotime($leave['end_date'])); ?>
                                    </td>
                                    <td><strong><?php echo $days; ?> hari</strong></td>
                                    <td>
                                        <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                            <?php echo htmlspecialchars(substr($leave['reason'], 0, 40)) . (strlen($leave['reason']) > 40 ? '...' : ''); ?>
                                        </span>
                                    </td>
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
                                    <td>
                                        <?php if ($leave['status'] === 'PENDING'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-approve" onclick="return confirm('Setujui pengajuan cuti ini?')">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-reject" onclick="return confirm('Tolak pengajuan cuti ini?')">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
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

<?php require_once 'includes/footer.php'; ?>

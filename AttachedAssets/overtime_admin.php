<?php
$pageTitle = 'Kelola Lembur - HRMS';
require_once 'config/database.php';
require_once 'includes/session.php';

requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $overtimeId = intval($_POST['overtime_id'] ?? 0);
    $action = $_POST['action'];
    $adminId = getCurrentUserId();

    if ($overtimeId > 0 && in_array($action, ['approve', 'reject'])) {
        $newStatus = ($action === 'approve') ? 'APPROVED' : 'REJECTED';
        
        try {
            $stmt = $conn->prepare("
                UPDATE overtime 
                SET status = :status, approved_by = :admin_id, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $newStatus,
                ':admin_id' => $adminId,
                ':id' => $overtimeId
            ]);

            $success = 'Pengajuan lembur berhasil ' . ($action === 'approve' ? 'disetujui' : 'ditolak') . '!';
        } catch (PDOException $e) {
            error_log("Admin action error: " . $e->getMessage());
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}

$filterDate = $_GET['date'] ?? '';
$filterEmployee = $_GET['employee'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$whereConditions = [];
$params = [];

if ($filterDate) {
    $whereConditions[] = "o.date = :date";
    $params[':date'] = $filterDate;
}
if ($filterEmployee) {
    $whereConditions[] = "u.full_name ILIKE :employee";
    $params[':employee'] = '%' . $filterEmployee . '%';
}
if ($filterStatus) {
    $whereConditions[] = "o.status = :status";
    $params[':status'] = $filterStatus;
}

$whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "
    SELECT o.*, u.full_name, u.username,
           (SELECT full_name FROM users WHERE id = o.approved_by) as approver_name
    FROM overtime o 
    JOIN users u ON o.employee_id = u.id 
    $whereClause
    ORDER BY 
        CASE o.status WHEN 'PENDING' THEN 0 ELSE 1 END,
        o.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$overtimeList = $stmt->fetchAll();

$employeesStmt = $conn->prepare("SELECT id, full_name FROM users WHERE role = 'employee' ORDER BY full_name");
$employeesStmt->execute();
$employees = $employeesStmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-list-check me-2"></i>Kelola Pengajuan Lembur</h1>
        <p class="text-muted">Setujui atau tolak pengajuan lembur karyawan</p>
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
            <div class="col-md-3">
                <label for="date" class="form-label">Tanggal</label>
                <input type="date" class="form-control" id="date" name="date" 
                       value="<?php echo htmlspecialchars($filterDate); ?>">
            </div>
            <div class="col-md-3">
                <label for="employee" class="form-label">Nama Karyawan</label>
                <input type="text" class="form-control" id="employee" name="employee" 
                       placeholder="Cari nama..." value="<?php echo htmlspecialchars($filterEmployee); ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua Status</option>
                    <option value="PENDING" <?php echo $filterStatus === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                    <option value="APPROVED" <?php echo $filterStatus === 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                    <option value="REJECTED" <?php echo $filterStatus === 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="overtime_admin.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($overtimeList)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h5>Tidak Ada Data</h5>
                    <p>Tidak ada pengajuan lembur yang sesuai dengan filter.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Karyawan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Durasi</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overtimeList as $ot): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ot['full_name']); ?></strong>
                                        <br><small class="text-muted">@<?php echo htmlspecialchars($ot['username']); ?></small>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($ot['date'])); ?></td>
                                    <td>
                                        <?php echo date('H:i', strtotime($ot['start_time'])); ?> - 
                                        <?php echo date('H:i', strtotime($ot['end_time'])); ?>
                                    </td>
                                    <td>
                                        <strong>
                                        <?php 
                                            $hours = floor($ot['duration'] / 60);
                                            $mins = $ot['duration'] % 60;
                                            echo "{$hours}j {$mins}m";
                                        ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($ot['reason']); ?>">
                                            <?php echo htmlspecialchars(substr($ot['reason'], 0, 40)) . (strlen($ot['reason']) > 40 ? '...' : ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $badgeClass = 'badge-pending';
                                            if ($ot['status'] === 'APPROVED') $badgeClass = 'badge-approved';
                                            if ($ot['status'] === 'REJECTED') $badgeClass = 'badge-rejected';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $ot['status']; ?></span>
                                        <?php if ($ot['approver_name']): ?>
                                            <br><small class="text-muted">oleh <?php echo htmlspecialchars($ot['approver_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ot['status'] === 'PENDING'): ?>
                                            <button type="button" class="btn btn-sm btn-approve btn-approve-modal" 
                                                    data-bs-toggle="modal" data-bs-target="#confirmModal"
                                                    data-id="<?php echo $ot['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($ot['full_name']); ?>">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-reject btn-reject-modal"
                                                    data-bs-toggle="modal" data-bs-target="#confirmModal"
                                                    data-id="<?php echo $ot['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($ot['full_name']); ?>">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
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

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Konfirmasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="overtime_id" id="confirmActionId">
                    <input type="hidden" name="action" id="confirmActionType">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="confirmActionBtn">Konfirmasi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

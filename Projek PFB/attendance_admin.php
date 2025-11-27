<?php
$pageTitle = 'Kelola Attendance - HRMS';
require_once 'config/database.php';
require_once 'config/init_db.php';
require_once 'includes/session.php';

initializeDatabase();
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterEmployee = $_GET['employee'] ?? '';

$whereConditions = ["a.date = :date"];
$params = [':date' => $filterDate];

if ($filterEmployee) {
    $whereConditions[] = "u.full_name ILIKE :employee";
    $params[':employee'] = '%' . $filterEmployee . '%';
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

$query = "
    SELECT a.*, u.full_name, u.department 
    FROM attendance a 
    JOIN users u ON a.employee_id = u.id 
    $whereClause
    ORDER BY a.check_in DESC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$attendanceList = $stmt->fetchAll();

$stmtStats = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN work_type = 'WFO' THEN 1 END) as wfo,
        COUNT(CASE WHEN work_type = 'WFH' THEN 1 END) as wfh
    FROM attendance 
    WHERE date = :date
");
$stmtStats->execute([':date' => $filterDate]);
$stats = $stmtStats->fetch();

$stmtEmployees = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
$stmtEmployees->execute();
$totalEmployees = $stmtEmployees->fetch()['total'];

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-calendar-check me-2"></i>Kelola Attendance</h1>
        <p class="text-muted">Monitor kehadiran karyawan</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-0"><?php echo $stats['total']; ?>/<?php echo $totalEmployees; ?></h3>
                    <small class="text-muted">Hadir Hari Ini</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <h3 class="text-info mb-0"><?php echo $stats['wfo']; ?></h3>
                    <small class="text-muted">WFO</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card success h-100">
                <div class="card-body text-center">
                    <h3 class="text-success mb-0"><?php echo $stats['wfh']; ?></h3>
                    <small class="text-muted">WFH</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card danger h-100">
                <div class="card-body text-center">
                    <h3 class="text-danger mb-0"><?php echo $totalEmployees - $stats['total']; ?></h3>
                    <small class="text-muted">Tidak Hadir</small>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-section">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="date" class="form-label">Tanggal</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo $filterDate; ?>">
            </div>
            <div class="col-md-4">
                <label for="employee" class="form-label">Nama Karyawan</label>
                <input type="text" class="form-control" id="employee" name="employee" 
                       placeholder="Cari nama..." value="<?php echo htmlspecialchars($filterEmployee); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="attendance_admin.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Data Kehadiran - <?php echo date('d F Y', strtotime($filterDate)); ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($attendanceList)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h5>Tidak Ada Data</h5>
                    <p>Tidak ada data kehadiran untuk tanggal ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Karyawan</th>
                                <th>Tipe</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Durasi</th>
                                <th>Lokasi</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceList as $att): 
                                $duration = '';
                                if ($att['check_in'] && $att['check_out']) {
                                    $diff = strtotime($att['check_out']) - strtotime($att['check_in']);
                                    $hours = floor($diff / 3600);
                                    $mins = floor(($diff % 3600) / 60);
                                    $duration = "{$hours}j {$mins}m";
                                }
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($att['full_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($att['department']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $att['work_type'] === 'WFO' ? 'primary' : 'success'; ?>">
                                            <?php echo $att['work_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $att['check_in'] ? date('H:i', strtotime($att['check_in'])) : '-'; ?></strong>
                                    </td>
                                    <td>
                                        <?php echo $att['check_out'] ? date('H:i', strtotime($att['check_out'])) : '-'; ?>
                                    </td>
                                    <td><?php echo $duration ?: '-'; ?></td>
                                    <td><?php echo htmlspecialchars($att['location'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($att['notes'] ?: '-'); ?></td>
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

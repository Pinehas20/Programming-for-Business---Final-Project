<?php
$pageTitle = 'Payslip - HRMS';
require_once 'config/database.php';
require_once 'config/init_db.php';
require_once 'includes/session.php';

initializeDatabase();
requireLogin();

if (isAdmin()) {
    header('Location: home.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$userId = getCurrentUserId();

$stmtPayslips = $conn->prepare("
    SELECT * FROM payslips 
    WHERE employee_id = :user_id 
    ORDER BY period_year DESC, period_month DESC
");
$stmtPayslips->execute([':user_id' => $userId]);
$payslips = $stmtPayslips->fetchAll();

$stmtUser = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmtUser->execute([':user_id' => $userId]);
$userData = $stmtUser->fetch();

$viewPayslip = null;
if (isset($_GET['view'])) {
    $viewId = intval($_GET['view']);
    $stmtView = $conn->prepare("SELECT * FROM payslips WHERE id = :id AND employee_id = :user_id");
    $stmtView->execute([':id' => $viewId, ':user_id' => $userId]);
    $viewPayslip = $stmtView->fetch();
}

require_once 'includes/header.php';

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-wallet2 me-2"></i>Payslip</h1>
        <p class="text-muted">Slip gaji dan riwayat pembayaran</p>
    </div>

    <?php if ($viewPayslip): ?>
    <div class="card mb-4" id="payslip-detail">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Slip Gaji - <?php echo $months[$viewPayslip['period_month']]; ?> <?php echo $viewPayslip['period_year']; ?></h5>
            <div>
                <button onclick="window.print()" class="btn btn-light btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
                <a href="payslip.php" class="btn btn-outline-light btn-sm"><i class="bi bi-x"></i></a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted">Informasi Karyawan</h6>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td width="150">Nama</td>
                            <td><strong><?php echo htmlspecialchars($userData['full_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Departemen</td>
                            <td><?php echo htmlspecialchars($userData['department']); ?></td>
                        </tr>
                        <tr>
                            <td>Posisi</td>
                            <td><?php echo htmlspecialchars($userData['position']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Periode Gaji</h6>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td width="150">Periode</td>
                            <td><strong><?php echo $months[$viewPayslip['period_month']]; ?> <?php echo $viewPayslip['period_year']; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Tanggal Bayar</td>
                            <td><?php echo $viewPayslip['payment_date'] ? date('d F Y', strtotime($viewPayslip['payment_date'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="badge bg-<?php echo $viewPayslip['status'] === 'PAID' ? 'success' : 'warning'; ?>">
                                    <?php echo $viewPayslip['status']; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success"><i class="bi bi-plus-circle me-1"></i>Pendapatan</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Gaji Pokok</td>
                            <td class="text-end">Rp <?php echo number_format($viewPayslip['basic_salary'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>Lembur</td>
                            <td class="text-end">Rp <?php echo number_format($viewPayslip['overtime_pay'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>Tunjangan</td>
                            <td class="text-end">Rp <?php echo number_format($viewPayslip['allowances'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr class="table-success">
                            <td><strong>Total Pendapatan</strong></td>
                            <td class="text-end"><strong>Rp <?php echo number_format($viewPayslip['basic_salary'] + $viewPayslip['overtime_pay'] + $viewPayslip['allowances'], 0, ',', '.'); ?></strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-danger"><i class="bi bi-dash-circle me-1"></i>Potongan</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Potongan</td>
                            <td class="text-end">Rp <?php echo number_format($viewPayslip['deductions'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>Pajak (PPh 21)</td>
                            <td class="text-end">Rp <?php echo number_format($viewPayslip['tax'], 0, ',', '.'); ?></td>
                        </tr>
                        <tr class="table-danger">
                            <td><strong>Total Potongan</strong></td>
                            <td class="text-end"><strong>Rp <?php echo number_format($viewPayslip['deductions'] + $viewPayslip['tax'], 0, ',', '.'); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-12">
                    <div class="bg-primary bg-opacity-10 p-4 rounded text-center">
                        <h6 class="text-muted mb-2">GAJI BERSIH (Take Home Pay)</h6>
                        <h2 class="text-primary mb-0">Rp <?php echo number_format($viewPayslip['net_salary'], 0, ',', '.'); ?></h2>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-muted small text-center">
                <p class="mb-0">Slip gaji ini dihasilkan secara otomatis oleh sistem.</p>
                <p class="mb-0">Jika ada pertanyaan, silakan hubungi HRD.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Riwayat Slip Gaji</h5>
        </div>
        <div class="card-body">
            <?php if (empty($payslips)): ?>
                <div class="empty-state">
                    <i class="bi bi-wallet"></i>
                    <h5>Belum Ada Data</h5>
                    <p>Slip gaji Anda akan muncul di sini setelah tersedia.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Periode</th>
                                <th>Gaji Pokok</th>
                                <th>Pendapatan Lain</th>
                                <th>Potongan</th>
                                <th>Gaji Bersih</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payslips as $ps): ?>
                                <tr>
                                    <td><strong><?php echo $months[$ps['period_month']]; ?> <?php echo $ps['period_year']; ?></strong></td>
                                    <td>Rp <?php echo number_format($ps['basic_salary'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($ps['overtime_pay'] + $ps['allowances'], 0, ',', '.'); ?></td>
                                    <td class="text-danger">-Rp <?php echo number_format($ps['deductions'] + $ps['tax'], 0, ',', '.'); ?></td>
                                    <td><strong class="text-success">Rp <?php echo number_format($ps['net_salary'], 0, ',', '.'); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $ps['status'] === 'PAID' ? 'success' : 'warning'; ?>">
                                            <?php echo $ps['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?view=<?php echo $ps['id']; ?>#payslip-detail" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Lihat
                                        </a>
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

<style>
@media print {
    .navbar, .page-header, .card:not(#payslip-detail), footer, .btn {
        display: none !important;
    }
    #payslip-detail {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>

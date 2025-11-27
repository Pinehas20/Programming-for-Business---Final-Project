<?php
$pageTitle = 'Kirim Pesan - HRMS';
require_once 'config/database.php';
require_once 'config/init_db.php';
require_once 'includes/session.php';

initializeDatabase();
requireAdmin();

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

$stmtEmployees = $conn->prepare("SELECT id, full_name, department FROM users WHERE role = 'employee' ORDER BY full_name");
$stmtEmployees->execute();
$employees = $stmtEmployees->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = $_POST['recipients'] ?? [];
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $messageType = $_POST['message_type'] ?? 'general';
    $isImportant = isset($_POST['is_important']);
    $sendToAll = isset($_POST['send_to_all']);

    if (empty($subject) || empty($message)) {
        $error = 'Subject dan pesan harus diisi!';
    } elseif (!$sendToAll && empty($recipients)) {
        $error = 'Pilih minimal satu penerima!';
    } else {
        try {
            $senderId = getCurrentUserId();
            $senderName = getCurrentUserName();
            
            if ($sendToAll) {
                $recipientIds = array_column($employees, 'id');
            } else {
                $recipientIds = $recipients;
            }

            $stmt = $conn->prepare("
                INSERT INTO inbox (recipient_id, sender_id, sender_name, subject, message, message_type, is_important) 
                VALUES (:recipient, :sender, :name, :subject, :message, :type, :important)
            ");

            foreach ($recipientIds as $recipientId) {
                $stmt->execute([
                    ':recipient' => $recipientId,
                    ':sender' => $senderId,
                    ':name' => $senderName,
                    ':subject' => $subject,
                    ':message' => $message,
                    ':type' => $messageType,
                    ':important' => $isImportant ? 'true' : 'false'
                ]);
            }

            $success = 'Pesan berhasil dikirim ke ' . count($recipientIds) . ' penerima!';
            $_POST = [];
        } catch (PDOException $e) {
            error_log("Send message error: " . $e->getMessage());
            $error = 'Terjadi kesalahan saat mengirim pesan.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-envelope-plus me-2"></i>Kirim Pesan</h1>
        <p class="text-muted">Kirim pesan ke karyawan</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
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
                            <label class="form-label"><i class="bi bi-people me-1"></i>Penerima</label>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="send_to_all" name="send_to_all" 
                                       onchange="toggleRecipients(this)">
                                <label class="form-check-label" for="send_to_all">
                                    <strong>Kirim ke Semua Karyawan</strong>
                                </label>
                            </div>
                            
                            <select class="form-select" id="recipients" name="recipients[]" multiple size="5">
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['full_name']); ?> - <?php echo htmlspecialchars($emp['department']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Tahan Ctrl/Cmd untuk memilih beberapa penerima</small>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="subject" class="form-label"><i class="bi bi-chat-left-text me-1"></i>Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" 
                                           value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" 
                                           placeholder="Subject pesan..." required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="message_type" class="form-label"><i class="bi bi-tag me-1"></i>Tipe</label>
                                    <select class="form-select" id="message_type" name="message_type">
                                        <option value="general">General</option>
                                        <option value="announcement">Announcement</option>
                                        <option value="payroll">Payroll</option>
                                        <option value="reminder">Reminder</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label"><i class="bi bi-text-paragraph me-1"></i>Pesan</label>
                            <textarea class="form-control" id="message" name="message" rows="8" 
                                      placeholder="Tulis pesan Anda di sini..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_important" name="is_important">
                                <label class="form-check-label" for="is_important">
                                    <i class="bi bi-star text-warning me-1"></i>Tandai sebagai Penting
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>Kirim Pesan
                            </button>
                            <a href="home.php" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">Pilih <strong>"Kirim ke Semua"</strong> untuk broadcast ke semua karyawan</li>
                        <li class="mb-2">Gunakan tipe <strong>Announcement</strong> untuk pengumuman penting</li>
                        <li class="mb-2">Tandai sebagai <strong>Penting</strong> agar pesan ditandai dengan bintang</li>
                        <li>Pesan akan langsung masuk ke inbox penerima</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRecipients(checkbox) {
    document.getElementById('recipients').disabled = checkbox.checked;
}
</script>

<?php require_once 'includes/footer.php'; ?>

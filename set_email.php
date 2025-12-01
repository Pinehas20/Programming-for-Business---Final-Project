<?php

$pageTitle = 'Verifikasi Email - HRMS';
require_once 'config/database.php';
require_once 'includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['pending_otp_user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$error = '';
$userId = $_SESSION['pending_otp_user_id'];
$fullName = $_SESSION['pending_otp_fullname'] ?? '';

$stmt = $conn->prepare("SELECT email FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
$savedEmail = $userRecord['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        try {
            $updateStmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :id");
            $updateStmt->execute([':email' => $email, ':id' => $userId]);
        } catch (PDOException $e) {
            error_log("Failed to save email: " . $e->getMessage());
        }
        
        $_SESSION['pending_otp_email'] = $email;
        header('Location: send_otp.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .email-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .email-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .email-icon i {
            font-size: 36px;
            color: white;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="email-card">
        <div class="text-center">
            <div class="email-icon">
                <i class="bi bi-envelope-at"></i>
            </div>
            <h4 class="mb-2">Verifikasi 2 Langkah</h4>
            <p class="text-muted mb-4">
                Halo <strong><?php echo htmlspecialchars($fullName); ?></strong>,<br>
                Masukkan email pribadi Anda untuk menerima kode verifikasi OTP.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i>Email Pribadi Anda
                </label>
                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                       placeholder="email.anda@gmail.com" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? $savedEmail ?? ''); ?>" 
                       required autofocus>
                <div class="form-text">
                    <i class="bi bi-shield-lock me-1"></i>
                    Kode OTP 6 digit akan dikirim ke email ini untuk verifikasi
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="bi bi-arrow-right me-2"></i>Lanjutkan
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="logout.php" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Batalkan dan kembali ke login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

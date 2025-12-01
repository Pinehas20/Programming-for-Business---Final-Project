<?php

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/otp_handler.php';
require_once 'includes/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['pending_otp_user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_SESSION['pending_otp_email']) || empty($_SESSION['pending_otp_email'])) {
    header('Location: set_email.php');
    exit();
}

$userId = $_SESSION['pending_otp_user_id'];
$userEmail = $_SESSION['pending_otp_email'];
$fullName = $_SESSION['pending_otp_fullname'] ?? 'User';

$otpHandler = new OTPHandler();
$canResend = $otpHandler->canResendOTP($userId);

if (!$canResend['can_resend'] && !isset($_GET['force'])) {
    $_SESSION['otp_wait_seconds'] = $canResend['wait_seconds'];
    header('Location: verify_otp.php');
    exit();
}

$result = $otpHandler->createOTP($userId, $userEmail);

if (!$result['success']) {
    $_SESSION['otp_error'] = $result['message'];
    header('Location: verify_otp.php');
    exit();
}

$mailer = new Mailer();

if (!$mailer->isConfigured()) {
    error_log("Demo OTP for user $userId: " . $result['otp']);
    $_SESSION['otp_warning'] = 'SMTP belum dikonfigurasi. Kode OTP ditampilkan di bawah (hanya untuk demo).';
    $_SESSION['demo_otp'] = $result['otp'];
    header('Location: verify_otp.php');
    exit();
}

$sendResult = $mailer->sendOTP($userEmail, $fullName, $result['otp']);

if ($sendResult['success']) {
    $_SESSION['otp_success'] = 'Kode OTP telah dikirim ke ' . $userEmail;
} else {
    error_log("Failed to send OTP for user $userId to $userEmail: " . $sendResult['message']);
    $_SESSION['otp_warning'] = 'Gagal mengirim email. Kode OTP ditampilkan di bawah (mode demo).';
    $_SESSION['demo_otp'] = $result['otp'];
}

header('Location: verify_otp.php');
exit();

<?php
require_once __DIR__ . '/session.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'HRMS - Human Resources Management System'; ?></title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="home.php">
                <img src="images/logo.png" alt="HRMS" height="32" class="me-2 navbar-logo">
                <span>HRMS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php"><i class="bi bi-house me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php"><i class="bi bi-calendar-check me-1"></i>Attendance</a>
                    </li>
                    <?php if (!isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="overtime.php"><i class="bi bi-clock me-1"></i>Overtime</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leave.php"><i class="bi bi-calendar-x me-1"></i>Leave</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payslip.php"><i class="bi bi-wallet2 me-1"></i>Payslip</a>
                    </li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="overtime_admin.php"><i class="bi bi-clock me-2"></i>Kelola Overtime</a></li>
                            <li><a class="dropdown-item" href="leave_admin.php"><i class="bi bi-calendar-x me-2"></i>Kelola Leave</a></li>
                            <li><a class="dropdown-item" href="attendance_admin.php"><i class="bi bi-calendar-check me-2"></i>Kelola Attendance</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="inbox_compose.php"><i class="bi bi-envelope-plus me-2"></i>Kirim Pesan</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="inbox.php">
                            <i class="bi bi-envelope me-1"></i>Inbox
                            <?php
                            $unreadCount = getUnreadInboxCount();
                            if ($unreadCount > 0):
                            ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars(getCurrentUserName()); ?>
                            <span class="badge bg-<?php echo isAdmin() ? 'danger' : 'info'; ?> ms-1"><?php echo ucfirst(getCurrentUserRole()); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="<?php echo isLoggedIn() ? 'py-4' : ''; ?>">

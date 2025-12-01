
CREATE DATABASE IF NOT EXISTS hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrms_db;


DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS company_feeds;
DROP TABLE IF EXISTS kpi;
DROP TABLE IF EXISTS inbox;
DROP TABLE IF EXISTS payslips;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS overtime;
DROP TABLE IF EXISTS users;


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    department VARCHAR(100),
    position VARCHAR(100),
    salary DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE overtime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration DECIMAL(4,2),
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_overtime_employee (employee_id),
    INDEX idx_overtime_date (date),
    INDEX idx_overtime_status (status),
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    work_type ENUM('WFO', 'WFH') DEFAULT 'WFO',
    location VARCHAR(255),
    notes TEXT,
    status ENUM('present', 'absent', 'late', 'early_leave') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attendance_employee (employee_id),
    INDEX idx_attendance_date (date),
    UNIQUE KEY unique_attendance (employee_id, date),
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type ENUM('annual', 'sick', 'personal', 'maternity', 'paternity', 'unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_leave_employee (employee_id),
    INDEX idx_leave_status (status),
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_month INT NOT NULL,
    period_year INT NOT NULL,
    basic_salary DECIMAL(15,2) DEFAULT 0,
    overtime_pay DECIMAL(15,2) DEFAULT 0,
    allowances DECIMAL(15,2) DEFAULT 0,
    deductions DECIMAL(15,2) DEFAULT 0,
    tax DECIMAL(15,2) DEFAULT 0,
    net_salary DECIMAL(15,2) DEFAULT 0,
    payment_date DATE,
    status ENUM('draft', 'generated', 'paid') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payslip_employee (employee_id),
    UNIQUE KEY unique_payslip (employee_id, period_month, period_year),
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inbox (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    sender_id INT,
    sender_name VARCHAR(100),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_important TINYINT(1) DEFAULT 0,
    message_type ENUM('info', 'warning', 'announcement', 'personal') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inbox_recipient (recipient_id),
    INDEX idx_inbox_read (is_read),
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kpi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_month INT NOT NULL,
    period_year INT NOT NULL,
    target_value DECIMAL(10,2) DEFAULT 0,
    actual_value DECIMAL(10,2) DEFAULT 0,
    category VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kpi_employee (employee_id),
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE company_feeds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    feed_type ENUM('announcement', 'news', 'event', 'achievement') DEFAULT 'announcement',
    is_pinned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts INT DEFAULT 0,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_user (user_id),
    INDEX idx_otp_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO users (username, password, full_name, email, department, position, salary) VALUES
('budi', '$2y$10$x7xDYGXBuzwxr220D1aMrecbX9PkinzRJuDLAsW8yYOq6w984qEzy', 'Budi Santoso', 'budi@company.com', 'IT Department', 'Software Developer', 8000000.00),
('siti', '$2y$10$x7xDYGXBuzwxr220D1aMrecbX9PkinzRJuDLAsW8yYOq6w984qEzy', 'Siti Rahayu', 'siti@company.com', 'Finance', 'Accountant', 7500000.00),
('andi', '$2y$10$x7xDYGXBuzwxr220D1aMrecbX9PkinzRJuDLAsW8yYOq6w984qEzy', 'Andi Wijaya', 'andi@company.com', 'Marketing', 'Marketing Staff', 6500000.00);

INSERT INTO overtime (employee_id, date, start_time, end_time, duration, reason, status) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '18:00:00', '21:00:00', 3.00, 'Menyelesaikan project deadline', 'approved'),
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '18:00:00', '20:00:00', 2.00, 'Bug fixing urgent', 'approved'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '18:00:00', '22:00:00', 4.00, 'Deployment ke production', 'pending'),
(2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '17:00:00', '20:00:00', 3.00, 'Closing bulanan', 'approved'),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '18:00:00', '21:00:00', 3.00, 'Audit preparation', 'pending');


INSERT INTO attendance (employee_id, date, check_in, check_out, work_type, location, notes, status) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:00:00', '17:00:00', 'WFO', 'Kantor Pusat', NULL, 'present'),
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:30:00', '17:00:00', 'WFH', 'Rumah', 'Hujan deras', 'present'),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '17:00:00', 'WFO', 'Kantor Pusat', NULL, 'present'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:15:00', '17:00:00', 'WFO', 'Kantor Pusat', 'Macet', 'late'),
(2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '07:45:00', '17:00:00', 'WFO', 'Kantor Pusat', NULL, 'present'),
(2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', '17:00:00', 'WFO', 'Kantor Pusat', NULL, 'present'),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '16:00:00', 'WFH', 'Rumah', 'Ada keperluan keluarga', 'early_leave'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 'WFO', 'Kantor Pusat', NULL, 'present');

INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status) VALUES
(1, 'annual', DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 16 DAY), 'Liburan keluarga', 'pending'),
(1, 'sick', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 29 DAY), 'Sakit demam', 'approved'),
(2, 'personal', DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Mengurus dokumen penting', 'approved'),
(2, 'annual', DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'Mudik lebaran', 'pending');


INSERT INTO payslips (employee_id, period_month, period_year, basic_salary, overtime_pay, allowances, deductions, tax, net_salary, payment_date, status) VALUES
(1, 10, 2025, 8000000.00, 750000.00, 1500000.00, 200000.00, 500000.00, 9550000.00, '2025-10-25', 'paid'),
(1, 11, 2025, 8000000.00, 500000.00, 1500000.00, 150000.00, 480000.00, 9370000.00, '2025-11-25', 'paid'),
(1, 12, 2025, 8000000.00, 600000.00, 1500000.00, 100000.00, 490000.00, 9510000.00, '2025-12-25', 'pending'),

(2, 10, 2025, 7500000.00, 300000.00, 1200000.00, 100000.00, 420000.00, 8480000.00, '2025-10-25', 'paid'),
(2, 11, 2025, 7500000.00, 450000.00, 1200000.00, 150000.00, 440000.00, 8560000.00, '2025-11-25', 'paid'),
(2, 12, 2025, 7500000.00, 200000.00, 1200000.00, 50000.00, 410000.00, 8440000.00, '2025-12-25', 'pending'),

(3, 10, 2025, 6500000.00, 400000.00, 1000000.00, 100000.00, 350000.00, 7450000.00, '2025-10-25', 'paid'),
(3, 11, 2025, 6500000.00, 600000.00, 1000000.00, 200000.00, 380000.00, 7520000.00, '2025-11-25', 'paid'),
(3, 12, 2025, 6500000.00, 350000.00, 1000000.00, 50000.00, 340000.00, 7460000.00, '2025-12-25', 'pending');


INSERT INTO inbox (recipient_id, sender_id, sender_name, subject, message, is_read, is_important, message_type) VALUES
(1, NULL, 'HR Department', 'Selamat Datang di HRMS', 'Selamat datang di sistem HRMS! Silakan gunakan sistem ini untuk mengajukan lembur, cuti, dan melihat informasi kepegawaian Anda.', 1, 0, 'info'),
(1, NULL, 'HR Department', 'Pengumuman: Kebijakan WFH Baru', 'Mulai bulan ini, karyawan dapat mengajukan WFH maksimal 2 hari per minggu. Silakan koordinasi dengan atasan masing-masing.', 0, 1, 'announcement'),
(1, NULL, 'HR Department', 'Reminder: Deadline Pengajuan Cuti', 'Mohon untuk mengajukan cuti tahunan paling lambat H-7 sebelum tanggal cuti yang diinginkan.', 0, 0, 'warning'),
(2, NULL, 'HR Department', 'Selamat Datang di HRMS', 'Selamat datang di sistem HRMS! Silakan gunakan sistem ini untuk mengajukan lembur, cuti, dan melihat informasi kepegawaian Anda.', 1, 0, 'info'),
(2, NULL, 'HR Department', 'Pengumuman: Training Wajib', 'Seluruh karyawan diwajibkan mengikuti training keselamatan kerja pada tanggal 15 bulan depan.', 0, 1, 'announcement');


INSERT INTO kpi (employee_id, period_month, period_year, target_value, actual_value, category, description) VALUES
(1, 11, 2025, 100.00, 85.00, 'Project Delivery', 'Penyelesaian proyek tepat waktu'),
(1, 11, 2025, 100.00, 92.00, 'Code Quality', 'Kualitas kode dan dokumentasi'),
(1, 11, 2025, 100.00, 78.00, 'Bug Resolution', 'Penyelesaian bug dalam SLA'),
(1, 11, 2025, 100.00, 95.00, 'Team Collaboration', 'Kolaborasi dengan tim'),

(2, 11, 2025, 100.00, 98.00, 'Report Accuracy', 'Akurasi laporan keuangan'),
(2, 11, 2025, 100.00, 88.00, 'Processing Time', 'Waktu pemrosesan invoice'),
(2, 11, 2025, 100.00, 92.00, 'Budget Compliance', 'Kepatuhan anggaran'),
(2, 11, 2025, 100.00, 85.00, 'Audit Score', 'Skor audit internal'),

(3, 11, 2025, 100.00, 75.00, 'Lead Generation', 'Target leads baru'),
(3, 11, 2025, 100.00, 110.00, 'Social Media', 'Engagement media sosial'),
(3, 11, 2025, 100.00, 82.00, 'Campaign ROI', 'Return on Investment kampanye'),
(3, 11, 2025, 100.00, 90.00, 'Brand Awareness', 'Peningkatan brand awareness');


INSERT INTO company_feeds (author_id, title, content, feed_type, is_pinned) VALUES
(NULL, 'Selamat Datang di HRMS', 'Sistem HRMS (Human Resources Management System) kini telah aktif. Gunakan sistem ini untuk mengelola kehadiran, lembur, cuti, dan melihat slip gaji Anda.', 'announcement', 1),
(NULL, 'Pencapaian Q3 2025', 'Selamat kepada seluruh tim atas pencapaian target Q3 2025! Revenue meningkat 25% dibanding periode yang sama tahun lalu.', 'achievement', 0),
(NULL, 'Event: Company Gathering', 'Akan diadakan company gathering pada akhir bulan ini. Detail akan diinformasikan melalui email masing-masing.', 'event', 0),
(NULL, 'Tips: Work-Life Balance', 'Jangan lupa untuk menjaga keseimbangan antara pekerjaan dan kehidupan pribadi. Manfaatkan cuti tahunan Anda!', 'news', 0);

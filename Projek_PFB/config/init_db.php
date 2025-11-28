<?php
require_once __DIR__ . '/database.php';

function initializeDatabase() {
    if (!getenv('PGHOST')) {
        return true;
    }
    
    $database = new Database();
    $conn = $database->getConnection();

    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            department VARCHAR(100) DEFAULT 'General',
            position VARCHAR(100) DEFAULT 'Staff',
            salary DECIMAL(15,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS overtime (
            id SERIAL PRIMARY KEY,
            employee_id INTEGER NOT NULL,
            date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            duration DECIMAL(4,2),
            reason TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            approved_by INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS attendance (
            id SERIAL PRIMARY KEY,
            employee_id INTEGER NOT NULL,
            date DATE NOT NULL,
            check_in TIME,
            check_out TIME,
            work_type VARCHAR(10) DEFAULT 'WFO',
            location TEXT,
            notes TEXT,
            status VARCHAR(20) DEFAULT 'present',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS leave_requests (
            id SERIAL PRIMARY KEY,
            employee_id INTEGER NOT NULL,
            leave_type VARCHAR(50) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            reason TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            approved_by INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS payslips (
            id SERIAL PRIMARY KEY,
            employee_id INTEGER NOT NULL,
            period_month INTEGER NOT NULL,
            period_year INTEGER NOT NULL,
            basic_salary DECIMAL(15,2) NOT NULL,
            overtime_pay DECIMAL(15,2) DEFAULT 0,
            allowances DECIMAL(15,2) DEFAULT 0,
            deductions DECIMAL(15,2) DEFAULT 0,
            tax DECIMAL(15,2) DEFAULT 0,
            net_salary DECIMAL(15,2) NOT NULL,
            payment_date DATE,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS inbox (
            id SERIAL PRIMARY KEY,
            recipient_id INTEGER NOT NULL,
            sender_id INTEGER,
            sender_name VARCHAR(100),
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            is_important BOOLEAN DEFAULT FALSE,
            message_type VARCHAR(50) DEFAULT 'general',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS kpi (
            id SERIAL PRIMARY KEY,
            employee_id INTEGER NOT NULL,
            period_month INTEGER NOT NULL,
            period_year INTEGER NOT NULL,
            target_value DECIMAL(10,2) NOT NULL,
            actual_value DECIMAL(10,2) DEFAULT 0,
            category VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS company_feeds (
            id SERIAL PRIMARY KEY,
            author_id INTEGER,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            feed_type VARCHAR(50) DEFAULT 'announcement',
            is_pinned BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        "CREATE INDEX IF NOT EXISTS idx_overtime_employee ON overtime(employee_id)",
        "CREATE INDEX IF NOT EXISTS idx_overtime_status ON overtime(status)",
        "CREATE INDEX IF NOT EXISTS idx_overtime_date ON overtime(date)",
        "CREATE INDEX IF NOT EXISTS idx_attendance_employee ON attendance(employee_id)",
        "CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(date)",
        "CREATE INDEX IF NOT EXISTS idx_leave_employee ON leave_requests(employee_id)",
        "CREATE INDEX IF NOT EXISTS idx_inbox_recipient ON inbox(recipient_id)",
        "CREATE INDEX IF NOT EXISTS idx_kpi_employee ON kpi(employee_id)"
    ];

    try {
        foreach ($queries as $query) {
            $conn->exec($query);
        }

        $checkEmployee = $conn->prepare("SELECT id FROM users WHERE username = 'budi'");
        $checkEmployee->execute();
        
        if ($checkEmployee->rowCount() == 0) {
            $empPassword = password_hash('employee123', PASSWORD_DEFAULT);
            
            $insertEmp = $conn->prepare("INSERT INTO users (username, password, full_name, email, department, position, salary) VALUES ('budi', :password, 'Budi Santoso', 'budi@company.com', 'IT Department', 'Software Developer', 8000000)");
            $insertEmp->execute([':password' => $empPassword]);
            
            $insertEmp2 = $conn->prepare("INSERT INTO users (username, password, full_name, email, department, position, salary) VALUES ('siti', :password, 'Siti Rahayu', 'siti@company.com', 'Finance', 'Accountant', 7500000)");
            $insertEmp2->execute([':password' => $empPassword]);
            
            $insertEmp3 = $conn->prepare("INSERT INTO users (username, password, full_name, email, department, position, salary) VALUES ('andi', :password, 'Andi Wijaya', 'andi@company.com', 'Marketing', 'Marketing Staff', 6500000)");
            $insertEmp3->execute([':password' => $empPassword]);
        }

        insertSampleData($conn);

        return true;
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

function insertSampleData($conn) {
    try {
        $checkFeeds = $conn->prepare("SELECT COUNT(*) as count FROM company_feeds");
        $checkFeeds->execute();
        if ($checkFeeds->fetch()['count'] == 0) {
            $feeds = [
                ['Selamat Datang di HRMS', 'Selamat datang di sistem HRMS perusahaan kami. Gunakan sistem ini untuk mengelola kehadiran, cuti, lembur, dan melihat slip gaji Anda.', 'announcement', true],
                ['Kebijakan WFH Terbaru', 'Mulai bulan ini, karyawan diperbolehkan WFH maksimal 2 hari per minggu. Pastikan koordinasi dengan atasan langsung.', 'news', false],
                ['Town Hall Meeting', 'Town Hall Meeting bulanan akan diadakan hari Jumat minggu depan pukul 14:00 WIB via Zoom.', 'event', false]
            ];
            
            foreach ($feeds as $feed) {
                $stmt = $conn->prepare("INSERT INTO company_feeds (title, content, feed_type, is_pinned) VALUES (:title, :content, :type, :pinned)");
                $stmt->execute([':title' => $feed[0], ':content' => $feed[1], ':type' => $feed[2], ':pinned' => $feed[3] ? 'true' : 'false']);
            }
        }

        $checkKPI = $conn->prepare("SELECT COUNT(*) as count FROM kpi");
        $checkKPI->execute();
        if ($checkKPI->fetch()['count'] == 0) {
            $currentMonth = date('n');
            $currentYear = date('Y');
            
            $kpis = [
                [1, 'Produktivitas', 100, 85, 'Target penyelesaian tugas bulanan'],
                [1, 'Kehadiran', 100, 95, 'Persentase kehadiran tepat waktu'],
                [1, 'Kualitas Kerja', 100, 90, 'Tingkat kepuasan hasil kerja'],
                [2, 'Produktivitas', 100, 80, 'Target penyelesaian tugas bulanan'],
                [2, 'Kehadiran', 100, 92, 'Persentase kehadiran tepat waktu'],
                [2, 'Kualitas Kerja', 100, 88, 'Tingkat kepuasan hasil kerja']
            ];
            
            foreach ($kpis as $kpi) {
                $stmt = $conn->prepare("INSERT INTO kpi (employee_id, period_month, period_year, category, target_value, actual_value, description) VALUES (:emp, :month, :year, :cat, :target, :actual, :desc)");
                $stmt->execute([
                    ':emp' => $kpi[0], ':month' => $currentMonth, ':year' => $currentYear,
                    ':cat' => $kpi[1], ':target' => $kpi[2], ':actual' => $kpi[3], ':desc' => $kpi[4]
                ]);
            }
        }

        $checkPayslip = $conn->prepare("SELECT COUNT(*) as count FROM payslips");
        $checkPayslip->execute();
        if ($checkPayslip->fetch()['count'] == 0) {
            $currentMonth = date('n');
            $currentYear = date('Y');
            
            $payslips = [
                [1, 8000000, 500000, 1000000, 200000, 465000],
                [2, 7500000, 300000, 800000, 150000, 420000]
            ];
            
            foreach ($payslips as $ps) {
                $netSalary = $ps[1] + $ps[2] + $ps[3] - $ps[4] - $ps[5];
                $stmt = $conn->prepare("INSERT INTO payslips (employee_id, period_month, period_year, basic_salary, overtime_pay, allowances, deductions, tax, net_salary, payment_date, status) VALUES (:emp, :month, :year, :basic, :ot, :allow, :ded, :tax, :net, CURRENT_DATE, 'paid')");
                $stmt->execute([
                    ':emp' => $ps[0], ':month' => $currentMonth, ':year' => $currentYear,
                    ':basic' => $ps[1], ':ot' => $ps[2], ':allow' => $ps[3],
                    ':ded' => $ps[4], ':tax' => $ps[5], ':net' => $netSalary
                ]);
            }
        }

        $checkInbox = $conn->prepare("SELECT COUNT(*) as count FROM inbox");
        $checkInbox->execute();
        if ($checkInbox->fetch()['count'] == 0) {
            $messages = [
                [1, 'HR Department', 'Selamat Datang di HRMS', 'Selamat datang di sistem HRMS! Silakan gunakan sistem ini untuk kehadiran, lembur, cuti, dan slip gaji.', 'info'],
                [1, 'HR Department', 'Slip Gaji Tersedia', 'Slip gaji Anda untuk bulan ini sudah tersedia. Silakan cek menu Slip Gaji.', 'info'],
                [2, 'HR Department', 'Selamat Datang di HRMS', 'Selamat datang di sistem HRMS! Silakan gunakan sistem ini untuk kehadiran, lembur, cuti, dan slip gaji.', 'info'],
            ];
            
            foreach ($messages as $msg) {
                $stmt = $conn->prepare("INSERT INTO inbox (recipient_id, sender_name, subject, message, message_type) VALUES (:recip, :name, :subj, :msg, :type)");
                $stmt->execute([
                    ':recip' => $msg[0], ':name' => $msg[1],
                    ':subj' => $msg[2], ':msg' => $msg[3], ':type' => $msg[4]
                ]);
            }
        }

    } catch (PDOException $e) {
        error_log("Sample data insertion error: " . $e->getMessage());
    }
}

if (php_sapi_name() === 'cli' || (isset($_GET['init']) && $_GET['init'] === 'true')) {
    if (initializeDatabase()) {
        echo "Database initialized successfully!\n";
    } else {
        echo "Failed to initialize database.\n";
    }
}
?>

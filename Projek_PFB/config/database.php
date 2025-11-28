<?php
/**
 * Database Configuration untuk XAMPP (MySQL)
 * 
 * PETUNJUK PENGGUNAAN:
 * 1. Rename file ini menjadi database.php
 * 2. Sesuaikan konfigurasi di bawah sesuai pengaturan XAMPP Anda
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'hrms_db';
    private $username = 'root';        // Default XAMPP username
    private $password = '';            // Default XAMPP password (kosong)
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}

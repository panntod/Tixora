<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

/**
 * Controller Autentikasi untuk Manajemen Registrasi, Login, dan Logout
 * Menggunakan PHP Native OOP, PDO, dan Keamanan Sesi Ketat
 */
class AuthController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Fitur Registrasi Pengguna Baru (Default Role: USER)
     */
    public function register(): void {
        AuthHelper::initSession();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        // Ambil input dari JSON payload atau form POST
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $nama = trim($input['nama'] ?? '');
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $input['password'] ?? '';
        $roleKode = strtoupper(trim($input['role_kode'] ?? 'USER')); // Default role USER

        if (!$nama || !$email || strlen($password) < 8) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Data tidak valid. Nama, format email yang benar, dan password minimal 8 karakter wajib diisi.'
            ]);
            return;
        }

        // Validasi keberadaan role di database menggunakan Prepared Statement
        $stmtRole = $this->db->prepare("SELECT id FROM roles WHERE kode = :kode");
        $stmtRole->execute(['kode' => $roleKode]);
        $role = $stmtRole->fetch();

        if (!$role) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Role sistem tidak valid.']);
            return;
        }

        // Cek duplikasi email
        $stmtCheck = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmtCheck->execute(['email' => $email]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar di sistem.']);
            return;
        }

        // Enkripsi password menggunakan Bcrypt
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (nama, email, password, role_id) 
                VALUES (:nama, :email, :password, :role_id)
            ");
            $stmt->execute([
                'nama' => $nama,
                'email' => $email,
                'password' => $hashedPassword,
                'role_id' => $role['id']
            ]);

            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'Registrasi berhasil! Silakan masuk menggunakan akun Anda.'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Gagal mendaftarkan akun: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Fitur Login Pengguna & Pencegahan Session Fixation
     */
    public function login(): void {
        AuthHelper::initSession();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email dan password wajib diisi.']);
            return;
        }

        // Ambil data user beserta rolenya
        $stmt = $this->db->prepare("
            SELECT u.*, r.kode as role_kode 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.email = :email
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verifikasi password
        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Email atau password salah.']);
            return;
        }

        // Cek status aktif akun (Suspensi)
        if (isset($user['is_active']) && !$user['is_active']) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Akun Anda ditangguhkan. Alasan: ' . ($user['alasan_suspensi'] ?? 'Kebijakan Admin.')
            ]);
            return;
        }

        // Regenerasi session ID untuk pencegahan Session Hijacking / Fixation
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'role' => $user['role_kode']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil!',
            'data' => [
                'id' => $user['id'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'role' => $user['role_kode']
            ]
        ]);
    }

    /**
     * Fitur Logout & Pembersihan Session
     */
    public function logout(): void {
        AuthHelper::initSession();
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logout berhasil.']);
    }
}
<?php
session_start();

// 1. Keamanan Akses: Hanya Admin dan Guru yang boleh masuk
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] != "admin" && $_SESSION["role"] != "guru")) { 
    header("Location: auth/login.php");
    exit; 
}

// 2. Koneksi Database
$path = realpath(__DIR__ . '/../config/db.php');
if (file_exists($path)) {
    include($path);
} else {
    die("❌ File db.php tidak ditemukan!");
}

$user_id = $_SESSION["user_id"]; 
$role = $_SESSION["role"];

// 3. LOGIKA PEMROSESAN (PHP di satu tempat)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- A. PROSES UPDATE PROFIL ---
    if (isset($_POST['btn_update_profil'])) {
        $name = trim($_POST["name"]);
        $email = trim($_POST["email"]);

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $user_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('✅ Profil berhasil diperbarui!'); window.location='pengaturan_akun.php';</script>";
            exit;
        } else {
            echo "<script>alert('❌ Gagal update profil: " . addslashes($conn->error) . "');</script>";
        }
    }

    // --- B. PROSES UPDATE PASSWORD (Plaintext Mode sesuai permintaan) ---
    if (isset($_POST['btn_update_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass !== $confirm_pass) {
            echo "<script>alert('⚠️ Konfirmasi sandi baru tidak cocok!');</script>";
        } else {
            // Verifikasi sandi lama
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();

            if ($res && $current_pass === $res['password']) {
                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $new_pass, $user_id);
                if ($upd->execute()) {
                    echo "<script>alert('✅ Kata sandi berhasil diperbarui!'); window.location='pengaturan_akun.php';</script>";
                    exit;
                }
            } else {
                echo "<script>alert('❌ Kata sandi saat ini salah!');</script>";
            }
        }
    }

    // --- C. PROSES TAMBAH USER (Hanya Admin) ---
    if (isset($_POST['btn_tambah_user']) && $role === 'admin') {
        $n_name = trim($_POST["new_name"]);
        $n_email = trim($_POST["new_email"]);
        $n_pass = $_POST["new_password"];
        $n_role = $_POST["new_role"];

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $n_name, $n_email, $n_pass, $n_role);
        
        if ($stmt->execute()) {
            echo "<script>alert('✅ Pengguna baru berhasil didaftarkan!'); window.location='pengaturan_akun.php';</script>";
            exit;
        } else {
            echo "<script>alert('❌ Gagal: Email mungkin sudah digunakan.');</script>";
        }
    }
}

// 4. AMBIL DATA USER TERBARU UNTUK DITAMPILKAN
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Akun - Bank Soal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #4f46e5; --bg-light: #f8fafc; --text-dark: #1e293b; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; padding: 0.8rem 0; }
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); margin-bottom: 25px; }
        .section-title { font-weight: 700; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #475569; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1px solid #e2e8f0; }
        .btn-save { background-color: var(--primary-color); color: white; border-radius: 10px; padding: 10px 25px; font-weight: 600; border: none; transition: 0.3s; }
        .btn-save:hover { background-color: #4338ca; transform: translateY(-1px); }
        .admin-section { border-left: 5px solid var(--primary-color); background-color: #f5f3ff; }
        .info-badge { font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; background: #e0e7ff; color: #4338ca; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="admin.php">← KEMBALI KE DASHBOARD</a>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h3 class="fw-bold mb-4">⚙️ Pengaturan Akun</h3>

            <!-- Bagian 1: Profil -->
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="section-title">📧 Informasi Profil</h5>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user_data['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Email <span class="info-badge">ID Login</span></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                        </div>
                        <button type="submit" name="btn_update_profil" class="btn btn-save">Simpan Perubahan Profil</button>
                    </form>
                </div>
            </div>

            <!-- Bagian 2: Password -->
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="section-title">🔒 Keamanan Kata Sandi</h5>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Kata Sandi Saat Ini</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kata Sandi Baru</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Konfirmasi Sandi Baru</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" name="btn_update_password" class="btn btn-save">Perbarui Kata Sandi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
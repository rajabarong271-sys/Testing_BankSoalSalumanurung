<?php
session_start();

// Batasi akses hanya untuk admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../auth/login.php");
    exit;
}

// Include koneksi database
$path = realpath(__DIR__ . '/../config/db.php');
if (file_exists($path)) {
    include($path);
} else {
    die("❌ File db.php tidak ditemukan di: " . $path);
}

$message = "";

// Proses Hapus Siswa
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'siswa'");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        ✅ Siswa berhasil dihapus.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
}

// Tambah siswa baru
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if ($name && $email && $password) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'siswa')");
        $stmt->bind_param("sss", $name, $email, $password);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            ✅ Siswa <strong>$name</strong> berhasil ditambahkan.
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Gagal: " . htmlspecialchars($stmt->error) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Siswa | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-color: #0ea5e9; 
            --bg-light: #f8fafc;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-light); 
            padding-top: 80px;
        }
        .top-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 15px 40px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        .card { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
            background: white;
            margin-bottom: 2rem;
        }
        .card-header-siswa {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        .table thead {
            background-color: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .btn-primary { background-color: var(--primary-color); border: none; }
        .btn-primary:hover { background-color: #0284c7; }
        code { color: #0284c7; background: #f0f9ff; padding: 2px 5px; border-radius: 4px; }
        .back-link { text-decoration: none; color: #64748b; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .back-link:hover { color: var(--primary-color); }
    </style>
</head>
<body>

<nav class="top-nav shadow-sm">
    <a href="admin.php" class="back-link">
        <i>⬅️</i> Kembali ke Dashboard
    </a>
    <div class="fw-bold text-primary">MANAJEMEN SISWA</div>
</nav>

<div class="container mt-4">
    <div class="row">
        <!-- Form Tambah Siswa -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header-siswa">
                    <h5 class="mb-0 fw-bold">Tambah Siswa</h5>
                </div>
                <div class="card-body p-4">
                    <?= $message ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Nama Lengkap</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: Andi Wijaya" required autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Email Siswa</label>
                            <input type="email" name="email" id="email" class="form-control bg-light" placeholder="Otomatis..." readonly required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Password</label>
                            <div class="input-group">
                                <input type="text" name="password" id="passInput" class="form-control" value="Siswa<?= rand(100,999) ?>" required>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generatePass()">Acak</button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Daftarkan Siswa</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Siswa -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">📋 Database Siswa Terdaftar</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th>Identitas Siswa</th>
                                    <th>Kredensial</th>
                                    <th>Tgl Daftar</th>
                                    <th width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query("SELECT id, name, email, password, created_at FROM users WHERE role='siswa' ORDER BY id DESC");
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $tgl = date('d/m/Y', strtotime($row['created_at']));
                                        echo "<tr>
                                                <td><span class='text-muted'>#{$row['id']}</span></td>
                                                <td>
                                                    <div class='fw-bold text-dark'>".htmlspecialchars($row['name'])."</div>
                                                    <div class='small text-muted'>Role: Siswa</div>
                                                </td>
                                                <td>
                                                    <div class='mb-1'><code>".htmlspecialchars($row['email'])."</code></div>
                                                    <div class='small'>Pass: <span class='text-secondary'>".htmlspecialchars($row['password'])."</span></div>
                                                </td>
                                                <td><span class='text-muted small'>$tgl</span></td>
                                                <td>
                                                    <a href='?delete_id={$row['id']}' class='btn btn-sm btn-outline-danger border-0' onclick='return confirm(\"Hapus data siswa ini?\")'>
                                                        Hapus
                                                    </a>
                                                </td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada data siswa terdaftar.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');

    nameInput.addEventListener('input', function() {
        let nama = nameInput.value.trim().toLowerCase();
        nama = nama.replace(/\s+/g, '').replace(/[^a-z0-9]/g, '');
        emailInput.value = nama ? nama + '@siswa.local' : '';
    });

    function generatePass() {
        const randomNum = Math.floor(Math.random() * 900) + 100;
        document.getElementById('passInput').value = "Siswa" + randomNum;
    }
</script>
</body>
</html>
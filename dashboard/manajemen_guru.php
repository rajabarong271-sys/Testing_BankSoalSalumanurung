<?php
session_start();

// Batasi akses hanya untuk admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

$message = "";

// Proses Hapus Guru
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Pastikan admin tidak menghapus dirinya sendiri (opsional)
    if ($delete_id == $_SESSION['user_id']) {
        $message = "<div class='alert alert-warning alert-dismissible fade show'>
                        ⚠️ Anda tidak dapat menghapus akun Anda sendiri!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    } else {
        // Mulai transaksi untuk memastikan konsistensi data
        $conn->begin_transaction();
        try {
            // Hapus relasi mata pelajaran terlebih dahulu
            $stmt1 = $conn->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
            $stmt1->bind_param("i", $delete_id);
            $stmt1->execute();

            // Hapus user
            $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'guru'");
            $stmt2->bind_param("i", $delete_id);
            $stmt2->execute();

            $conn->commit();
            $message = "<div class='alert alert-success alert-dismissible fade show'>
                            ✅ Data guru berhasil dihapus dari sistem.
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>❌ Gagal menghapus data: " . $conn->error . "</div>";
        }
    }
}

// Proses Simpan Data Guru
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $subject_ids = isset($_POST["subjects"]) ? $_POST["subjects"] : [];

    if (empty($name) || empty($email) || empty($password)) {
        $message = "<div class='alert alert-warning alert-dismissible fade show'>
                        ⚠️ Nama, Email, dan Password wajib diisi!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    } else {
        // Cek apakah email sudah ada
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $res_email = $check_email->get_result();

        if ($res_email->num_rows > 0) {
            $message = "<div class='alert alert-danger alert-dismissible fade show'>
                            ❌ Email <strong>$email</strong> sudah terdaftar!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'guru')");
            $stmt->bind_param("sss", $name, $email, $password);

            if ($stmt->execute()) {
                $new_teacher_id = $conn->insert_id;
                if (!empty($subject_ids)) {
                    $stmt_rel = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
                    foreach ($subject_ids as $sub_id) {
                        $stmt_rel->bind_param("ii", $new_teacher_id, $sub_id);
                        $stmt_rel->execute();
                    }
                }
                $message = "<div class='alert alert-success alert-dismissible fade show'>
                                ✅ Guru <strong>$name</strong> berhasil ditambahkan.
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                            </div>";
            } else {
                $message = "<div class='alert alert-danger'>❌ Gagal menambah data: " . $conn->error . "</div>";
            }
        }
    }
}

// Ambil Data Mata Pelajaran untuk Form
$subjects_list = $conn->query("SELECT * FROM subjects ORDER BY name ASC");

// Ambil Data Guru untuk Tabel
$teachers_query = "SELECT u.id, u.name, u.email, u.password, u.created_at,
                   GROUP_CONCAT(s.name SEPARATOR ', ') as assigned_subjects 
                   FROM users u 
                   LEFT JOIN teacher_subjects ts ON u.id = ts.teacher_id 
                   LEFT JOIN subjects s ON ts.subject_id = s.id 
                   WHERE u.role = 'guru' 
                   GROUP BY u.id 
                   ORDER BY u.id DESC";
$teachers_result = $conn->query($teachers_query);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Guru | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-color: #4f46e5; 
            --bg-light: #f8fafc;
            --danger-color: #ef4444;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-light); 
            margin: 0;
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
            border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            background: white; 
            margin-bottom: 30px;
        }
        
        .card-header-gradient { 
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); 
            color: white; 
            border-radius: 20px 20px 0 0 !important; 
            padding: 25px; 
        }

        .subject-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 12px;
            background: #fff;
        }

        .subject-item {
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 8px 12px;
            transition: 0.2s;
            background: #fff;
            cursor: pointer;
        }
        
        .subject-item:hover { 
            border-color: var(--primary-color); 
            background: #f5f3ff; 
        }

        .back-link {
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-link:hover { color: var(--primary-color); }

        .badge-mapel {
            background-color: #eef2ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
            font-size: 0.75rem;
        }

        .btn-delete {
            color: var(--danger-color);
            background: #fef2f2;
            border: 1px solid #fee2e2;
            transition: 0.2s;
        }

        .btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }
        
        code { color: #d63384; font-weight: 500; }
    </style>
</head>
<body>

<nav class="top-nav shadow-sm">
    <a href="admin.php" class="back-link small">
        <i>⬅️</i> Kembali ke Dashboard
    </a>
    <div class="fw-bold text-primary">ADMIN PANEL</div>
</nav>

<div class="container pb-5">
    <!-- Form Registrasi -->
    <div class="card mx-auto shadow-sm" style="max-width: 900px;">
        <div class="card-header-gradient text-center">
            <h4 class="mb-1 fw-bold">Registrasi Guru Baru</h4>
            <p class="mb-0 small opacity-75">Daftarkan pengajar dan atur penugasan mata pelajaran.</p>
        </div>
        <div class="card-body p-4 p-md-5">
            <?= $message ?>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap Guru</label>
                        <input type="text" name="name" id="nameInput" class="form-control py-2" placeholder="Contoh: Budi Santoso" required oninput="syncEmail(this.value)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Email (Login ID)</label>
                        <input type="email" name="email" id="emailInput" class="form-control py-2" placeholder="nama@sekolah.com" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">Password Sementara</label>
                    <div class="input-group">
                        <input type="text" name="password" id="passInput" class="form-control py-2" value="GuruSMK<?= rand(100,999) ?>" required>
                        <button type="button" class="btn btn-dark" onclick="generatePass()">Acak Password</button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label d-block fw-bold mb-3">Tugaskan Mata Pelajaran</label>
                    <div class="subject-container">
                        <div class="row g-2">
                            <?php if($subjects_list->num_rows > 0): ?>
                                <?php while($s = $subjects_list->fetch_assoc()): ?>
                                <div class="col-md-6">
                                    <div class="subject-item">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="subjects[]" value="<?= $s['id'] ?>" id="s<?= $s['id'] ?>">
                                            <label class="form-check-label w-100 cursor-pointer" for="s<?= $s['id'] ?>">
                                                <?= htmlspecialchars($s['name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-2 text-muted small">Belum ada mata pelajaran tersedia.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                    Simpan dan Daftarkan Guru
                </button>
            </form>
        </div>
    </div>

    <!-- Tabel Monitoring & Hapus -->
    <div class="card mx-auto shadow-sm" style="max-width: 1000px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4">📋 Daftar Guru Terdaftar</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">ID</th>
                            <th>Nama Guru</th>
                            <th>Email & Password</th>
                            <th>Mata Pelajaran</th>
                            <th width="100" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($teachers_result->num_rows > 0): ?>
                            <?php while($t = $teachers_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $t['id'] ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($t['name']) ?></div>
                                    <small class="text-muted">Tgl Daftar: <?= date('d/m/Y', strtotime($t['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="mb-1"><code><?= htmlspecialchars($t['email']) ?></code></div>
                                    <div class="small text-muted">Pass: <?= htmlspecialchars($t['password']) ?></div>
                                </td>
                                <td>
                                    <?php 
                                    if($t['assigned_subjects']) {
                                        $sub_array = explode(', ', $t['assigned_subjects']);
                                        foreach($sub_array as $sa) {
                                            echo "<span class='badge badge-mapel rounded-pill px-2 me-1'>$sa</span>";
                                        }
                                    } else {
                                        echo "<span class='text-danger small'>Kosong</span>";
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="?delete_id=<?= $t['id'] ?>" 
                                       class="btn btn-sm btn-delete px-3 py-1 fw-semibold" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data guru ini? Semua relasi mata pelajaran akan terhapus.')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data guru.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function syncEmail(val) {
        const emailInput = document.getElementById('emailInput');
        let cleanName = val.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9]/g, '');
        
        if (cleanName.length > 0) {
            emailInput.value = cleanName + "@gmail.com";
        } else {
            emailInput.value = "";
        }
    }

    function generatePass() {
        const randomNum = Math.floor(Math.random() * 900) + 100;
        document.getElementById('passInput').value = "GuruSMK" + randomNum;
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
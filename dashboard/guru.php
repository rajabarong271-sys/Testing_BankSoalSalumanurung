<?php
session_start();

// 1. Keamanan Akses: Khusus Guru (Admin juga bisa akses sebagai fallback)
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] != "guru" && $_SESSION["role"] != "admin")) { 
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
$user_name = $_SESSION["name"];

// 3. Ambil Mata Pelajaran yang diampu oleh Guru ini
// Mengasumsikan ada tabel 'teacher_subjects' yang menghubungkan guru dengan mapel
$stmt = $conn->prepare("SELECT s.* FROM subjects s 
                        JOIN teacher_subjects ts ON ts.subject_id = s.id 
                        WHERE ts.teacher_id = ? ORDER BY s.name");
$stmt->bind_param("i", $user_id); 
$stmt->execute(); 
$subjects = $stmt->get_result(); 

$selected_subject = isset($_GET["subject_id"]) ? intval($_GET["subject_id"]) : 0;

// Statistik Sederhana untuk Guru
$count_soal = 0;
if ($selected_subject > 0) {
    $q_soal = $conn->prepare("SELECT COUNT(*) as total FROM questions WHERE subject_id = ?");
    $q_soal->bind_param("i", $selected_subject);
    $q_soal->execute();
    $count_soal = $q_soal->get_result()->fetch_assoc()['total'];
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Guru - Bank Soal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #a855f7;
            --bg-body: #f1f5f9;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: #1e293b; }
        
        /* Sidebar/Navbar Style */
        .navbar { background: #fff !important; border-bottom: 1px solid #e2e8f0; }
        .nav-link { color: #64748b; font-weight: 500; }
        .nav-link.active { color: var(--primary-color); }

        /* Welcome Section */
        .welcome-card {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        }

        /* Menu Cards */
        .menu-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
            height: 100%;
            background: #fff;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }
        .icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .bg-soft-blue { background: #e0e7ff; color: #4338ca; }
        .bg-soft-purple { background: #f3e8ff; color: #7e22ce; }
        .bg-soft-green { background: #dcfce7; color: #15803d; }
        .bg-soft-orange { background: #ffedd5; color: #c2410c; }

        .btn-action {
            border-radius: 10px;
            font-weight: 600;
            padding: 8px 20px;
        }
        
        .stat-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#">BANK SOAL GURU</a>
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <a class="text-decoration-none d-flex align-items-center gap-2 dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:35px; height:35px;">
                        <?= substr($user_name, 0, 1) ?>
                    </div>
                    <span class="d-none d-md-inline text-dark fw-medium"><?= htmlspecialchars($user_name) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item" href="pengaturan_akun.php">⚙️ Pengaturan Akun</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../auth/logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Welcome Header -->
    <div class="welcome-card d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="fw-bold mb-1">Selamat Datang, Bapak/Ibu <?= explode(' ', $user_name)[0] ?>! 👋</h2>
            <p class="opacity-75 mb-0">Siap untuk menyusun soal dan mengevaluasi hasil belajar siswa hari ini?</p>
        </div>
        <div class="mt-3 mt-md-0">
            <span class="stat-badge">Role: Guru Mata Pelajaran</span>
        </div>
    </div>

    <!-- Subject Selector -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form action="" method="GET" class="row align-items-center">
                <div class="col-md-7">
                    <label class="form-label fw-bold small text-muted text-uppercase">Pilih Mata Pelajaran Anda</label>
                    <select name="subject_id" class="form-select form-select-lg border-0 bg-light" onchange="this.form.submit()" style="border-radius: 12px;">
                        <option value="0">-- Pilih Mata Pelajaran --</option>
                        <?php 
                        $subjects->data_seek(0);
                        while($s = $subjects->fetch_assoc()): 
                        ?>
                            <option value="<?= $s['id'] ?>" <?= $selected_subject == $s['id'] ? 'selected' : '' ?>>
                                📖 <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if($selected_subject > 0): ?>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <div class="d-inline-block text-start me-4">
                        <div class="small text-muted">Total Bank Soal</div>
                        <div class="fw-bold h5 mb-0 text-primary"><?= $count_soal ?> Pertanyaan</div>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Main Menu -->
    <div class="row g-4 mb-5">
        <?php if($selected_subject > 0): ?>
            <!-- Menu 1: Bank Soal -->
            <div class="col-md-3">
                <div class="card menu-card shadow-sm p-4">
                    <div class="icon-wrapper bg-soft-blue">📚</div>
                    <h5 class="fw-bold">Manajemen Soal</h5>
                    <p class="text-muted small">Kelola butir soal pilihan ganda maupun esai.</p>
                    <div class="mt-auto">
                        <a href="../soal/index.php?subject_id=<?= $selected_subject ?>" class="btn btn-primary w-100 btn-action mb-2">Buka Bank Soal</a>
                        <a href="../soal/tambah.php?subject_id=<?= $selected_subject ?>" class="btn btn-outline-primary w-100 btn-action small text-nowrap">+ Soal Baru</a>
                    </div>
                </div>
            </div>

            <!-- Menu 2: Jadwal Ujian -->
            <div class="col-md-3">
                <div class="card menu-card shadow-sm p-4">
                    <div class="icon-wrapper bg-soft-purple">⏱️</div>
                    <h5 class="fw-bold">Pelaksanaan Ujian</h5>
                    <p class="text-muted small">Atur token, durasi, dan waktu aktif ujian.</p>
                    <div class="mt-auto">
                        <a href="../ujian/index.php?subject_id=<?= $selected_subject ?>" class="btn btn-primary w-100 btn-action">Atur Ujian</a>
                    </div>
                </div>
            </div>

            <!-- Menu 3: Koreksi Jawaban -->
            <div class="col-md-3">
                <div class="card menu-card shadow-sm p-4">
                    <div class="icon-wrapper bg-soft-green">✍️</div>
                    <h5 class="fw-bold">Koreksi Esai</h5>
                    <p class="text-muted small">Periksa jawaban terbuka dari siswa secara manual.</p>
                    <div class="mt-auto">
                        <a href="../nilai/nilai_index.php?subject_id=<?= $selected_subject ?>" class="btn btn-primary w-100 btn-action">Mulai Menilai</a>
                    </div>
                </div>
            </div>

            <!-- Menu 4: Analisis Hasil -->
            <div class="col-md-3">
                <div class="card menu-card shadow-sm p-4">
                    <div class="icon-wrapper bg-soft-orange">📊</div>
                    <h5 class="fw-bold">Rekap Nilai</h5>
                    <p class="text-muted small">Lihat statistik pencapaian dan ekspor nilai.</p>
                    <div class="mt-auto">
                        <a href="../hasil/rekap.php?subject_id=<?= $selected_subject ?>" class="btn btn-primary w-100 btn-action">Lihat Laporan</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="mb-3" style="font-size: 4rem;">💡</div>
                <h4 class="fw-bold">Pilih mata pelajaran terlebih dahulu</h4>
                <p class="text-muted">Gunakan menu drop-down di atas untuk mulai mengelola konten pembelajaran Anda.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer Information -->
    <div class="text-center pb-5 text-muted small">
        &copy; <?= date('Y') ?> Sistem Bank Soal Digital. Dikembangkan untuk efisiensi mengajar.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
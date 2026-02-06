<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"]!="admin" && $_SESSION["role"]!="guru")) { 
    header("Location: auth/login.php");
    exit; 
}
include("../config/db.php");

$user_id = $_SESSION["user_id"]; 
$role = $_SESSION["role"];

if ($role==="admin") { 
    $subjects = $conn->query("SELECT * FROM subjects ORDER BY name"); 
} else { 
    $stmt = $conn->prepare("SELECT s.* FROM subjects s 
                            JOIN teacher_subjects ts ON ts.subject_id=s.id 
                            WHERE ts.teacher_id=? ORDER BY s.name");
    $stmt->bind_param("i",$user_id); 
    $stmt->execute(); 
    $subjects=$stmt->get_result(); 
}
$selected = isset($_GET["subject_id"])?intval($_GET["subject_id"]):0;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard <?= ucfirst($role) ?> - Bank Soal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            padding: 0.8rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .navbar-brand { font-weight: 700; letter-spacing: 0.5px; }

        .nav-profile-dropdown .dropdown-toggle {
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .main-container { padding-top: 40px; padding-bottom: 60px; }

        .card { border: none; border-radius: 16px; transition: all 0.3s ease; overflow: hidden; }
        .card-shadow { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }

        .card-menu { height: 100%; border: 1px solid #f1f5f9; display: flex; flex-direction: column; }
        .card-menu:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1); }

        .icon-box {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px; font-size: 1.5rem;
        }

        .bg-icon-soal { background: #e0e7ff; color: #4338ca; }
        .bg-icon-ujian { background: #fef3c7; color: #d97706; }
        .bg-icon-nilai { background: #dcfce7; color: #15803d; }
        .bg-icon-hasil { background: #fae8ff; color: #a21caf; }
        .bg-icon-user { background: #e0f2fe; color: #0369a1; }
        .bg-icon-teacher { background: #ede9fe; color: #6d28d9; }

        .section-title {
            font-weight: 700; margin-bottom: 25px; color: var(--text-dark);
            position: relative; padding-left: 15px;
        }
        .section-title::before {
            content: ''; position: absolute; left: 0; top: 5px; bottom: 5px;
            width: 4px; background: var(--primary-color); border-radius: 2px;
        }

        .welcome-banner {
            background: white; padding: 30px; border-radius: 16px;
            margin-bottom: 30px; border-left: 6px solid var(--primary-color);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">BANK SOAL</a>
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown nav-profile-dropdown">
                <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-none d-md-inline me-2">Halo, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong></span>
                    <span class="badge bg-white text-primary"><?= strtoupper($role) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header">Manajemen Akun</h6></li>
                    <li><a class="dropdown-item" href="pengaturan_akun.php">⚙️ Pengaturan Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../auth/logout.php">🚪 Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container main-container">
    <div class="welcome-banner card-shadow d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">Selamat Datang di Panel Kendali</h4>
            <p class="text-muted mb-0">Kelola kurikulum, bank soal, dan hasil ujian siswa dalam satu tempat.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="pengaturan_akun.php" class="btn btn-outline-primary fw-bold rounded-pill">
                ⚙️ Akun
            </a>
        </div>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="get">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Pilih Mata Pelajaran Untuk Dikelola</label>
                    <select name="subject_id" class="form-select form-select-lg rounded-3" onchange="this.form.submit()">
                        <option value="0">-- Klik untuk memilih mata pelajaran --</option>
                        <?php 
                        $subjects->data_seek(0); 
                        while($s=$subjects->fetch_assoc()): 
                        ?>
                            <option value="<?= $s['id'] ?>" <?= $selected==$s['id']?'selected':'' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <a class="btn btn-outline-secondary btn-lg w-100 rounded-3" href="../mapel/index.php">
                        📂 Kelola Daftar Mapel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if($selected): ?>
    <h5 class="section-title">Menu Utama Mata Pelajaran</h5>
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card card-menu card-shadow">
                <div class="card-body p-4">
                    <div class="icon-box bg-icon-soal">📚</div>
                    <h6 class="fw-bold">Bank Soal</h6>
                    <p class="small text-muted mb-4">Buat dan edit koleksi pertanyaan ujian.</p>
                    <div class="d-grid gap-2 mt-auto">
                        <a class="btn btn-primary btn-sm rounded-pill" href="../soal/index.php?subject_id=<?= $selected ?>">Kelola Soal</a>
                        <a class="btn btn-outline-primary btn-sm rounded-pill" href="../soal/import_export.php?subject_id=<?= $selected ?>">Import/Export</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-menu card-shadow">
                <div class="card-body p-4">
                    <div class="icon-box bg-icon-ujian">📝</div>
                    <h6 class="fw-bold">Ujian</h6>
                    <p class="small text-muted mb-4">Atur jadwal, durasi, dan aktivasi ujian.</p>
                    <a class="btn btn-primary btn-sm w-100 mt-auto rounded-pill" href="../ujian/index.php?subject_id=<?= $selected ?>">Kelola Ujian</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-menu card-shadow">
                <div class="card-body p-4">
                    <div class="icon-box bg-icon-nilai">✍️</div>
                    <h6 class="fw-bold">Koreksi</h6>
                    <p class="small text-muted mb-4">Periksa dan beri nilai jawaban esai siswa.</p>
                    <a class="btn btn-primary btn-sm w-100 mt-auto rounded-pill" href="../nilai/nilai_index.php?subject_id=<?= $selected ?>">Periksa Jawaban</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card card-menu card-shadow">
                <div class="card-body p-4">
                    <div class="icon-box bg-icon-hasil">📊</div>
                    <h6 class="fw-bold">Laporan</h6>
                    <p class="small text-muted mb-4">Lihat statistik nilai dan cetak laporan.</p>
                    <a class="btn btn-primary btn-sm w-100 mt-auto rounded-pill" href="../hasil/index.php?subject_id=<?= $selected ?>">Laporan Nilai</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Administrasi Umum -->
    <h5 class="section-title">Administrasi Pengguna</h5>
    <div class="row g-4">
        <?php if ($role === "admin"): ?>
            <!-- Menu Khusus Guru -->
            <div class="col-md-4">
                <div class="card card-menu card-shadow">
                    <div class="card-body p-4">
                        <div class="icon-box bg-icon-teacher">👨‍🏫</div>
                        <h6 class="fw-bold">Manajemen Guru</h6>
                        <p class="small text-muted mb-4">Kelola akun pendidik dan pembagian mata pelajaran.</p>
                        <div class="d-grid gap-2 mt-auto">
                            <a href="manajemen_guru.php" class="btn btn-indigo btn-sm w-100 rounded-pill" style="background: #6d28d9; color: white;">Tambah Guru Baru</a>
                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Siswa -->
            <div class="col-md-4">
                <div class="card card-menu card-shadow">
                    <div class="card-body p-4">
                        <div class="icon-box bg-icon-user">👥</div>
                        <h6 class="fw-bold">Manajemen Siswa</h6>
                        <p class="small text-muted mb-4">Manajemen akun, data kelas, dan pendaftaran siswa baru.</p>
                        <div class="d-grid gap-2 mt-auto">
                            <a href="tambah_siswa.php" class="btn btn-success btn-sm w-100 rounded-pill">Tambah Siswa Baru</a>
                            
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Menu Profil (Untuk Semua Role) -->
        <div class="col-md-4">
            <div class="card card-menu card-shadow">
                <div class="card-body p-4">
                    <div class="icon-box bg-icon-settings">⚙️</div>
                    <h6 class="fw-bold">Pengaturan Akun</h6>
                    <p class="small text-muted mb-4">Ganti password dan perbarui informasi profil pribadi Anda.</p>
                    <a href="pengaturan_akun.php" class="btn btn-secondary btn-sm w-100 mt-auto rounded-pill">Buka Pengaturan</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
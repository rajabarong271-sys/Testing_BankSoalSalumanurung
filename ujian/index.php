<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"]!="admin" && $_SESSION["role"]!="guru")) { 
  header("Location: ../auth/login.php"); 
  exit; 
}
include("../config/db.php");
$user_id=$_SESSION['user_id']; $role=$_SESSION['role'];
$subject_id=isset($_GET['subject_id'])?intval($_GET['subject_id']):0; if(!$subject_id){ die("Subject tidak valid."); }
if ($role==='guru'){ $chk=$conn->prepare("SELECT 1 FROM teacher_subjects WHERE teacher_id=? AND subject_id=?"); $chk->bind_param("ii",$user_id,$subject_id); $chk->execute(); if(!$chk->get_result()->num_rows){ die("Tidak punya akses ke mapel ini."); } }
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['title'])){
  $title=trim($_POST['title']); $duration=intval($_POST['duration']); $start=$_POST['start_time']; $end=$_POST['end_time'];
  $stmt=$conn->prepare("INSERT INTO exams(subject_id,title,duration,start_time,end_time,created_by) VALUES (?,?,?,?,?,?)");
  $stmt->bind_param("isissi",$subject_id,$title,$duration,$start,$end,$user_id); $stmt->execute();
  header("Location: index.php?subject_id=".$subject_id); exit;
}
$exams=$conn->query("SELECT * FROM exams WHERE subject_id=".$subject_id." ORDER BY start_time DESC");
$subject=$conn->query("SELECT name FROM subjects WHERE id=".$subject_id)->fetch_assoc();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Ujian - <?= htmlspecialchars($subject['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --accent-color: #6366f1;
            --bg-soft: #f8fafc;
        }
        body { 
            background-color: var(--bg-soft); 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }
        .header-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 2.5rem 0 5rem;
            color: white;
            margin-bottom: -4rem;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #f1f5f9;
            padding: 1.25rem;
            font-weight: 700;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
        }
        .form-control {
            border-radius: 0.6rem;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
        }
        .btn-primary {
            background-color: var(--accent-color);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 0.6rem;
            font-weight: 600;
        }
        .table thead th {
            background-color: #f8fafc;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-top: none;
        }
        .exam-title {
            font-weight: 600;
            color: #0f172a;
            display: block;
        }
        .time-info {
            font-size: 0.85rem;
            color: #64748b;
        }
        .status-badge {
            font-size: 0.7rem;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-weight: 700;
        }
        .btn-action {
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="header-gradient">
    <div class="container text-center text-md-start">
        <div class="d-md-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">Manajemen Ujian</h2>
                <p class="mb-0 opacity-75"><i class="bi bi-book-half me-2"></i>Mata Pelajaran: <?= htmlspecialchars($subject['name']) ?></p>
            </div>
            <a href="../dashboard/admin.php?subject_id=<?= $subject_id ?>" class="btn btn-light btn-sm rounded-pill px-4 mt-3 mt-md-0 fw-bold shadow-sm">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <!-- Form Column -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-plus-circle-fill text-primary me-2"></i>Buat Jadwal Ujian Baru
                </div>
                <div class="card-body p-4">
                    <form class="row g-3" method="post">
                        <div class="col-md-4">
                            <label class="form-label">Judul/Nama Ujian</label>
                            <input class="form-control" name="title" placeholder="Contoh: Penilaian Tengah Semester" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Durasi (Menit)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="duration" placeholder="90" required>
                                <span class="input-group-text bg-light"><i class="bi bi-clock"></i></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Waktu Mulai</label>
                            <input type="datetime-local" class="form-control" name="start_time" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Waktu Selesai</label>
                            <input type="datetime-local" class="form-control" name="end_time" required>
                        </div>
                        <div class="col-12 text-end">
                            <hr class="my-3 opacity-50">
                            <button class="btn btn-primary px-5">
                                <i class="bi bi-calendar-check me-2"></i>Simpan Jadwal Ujian
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table Column -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ul text-primary me-2"></i>Daftar Jadwal Ujian</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2"><?= $exams->num_rows ?> Sesi Terdaftar</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Detail Ujian</th>
                                    <th>Durasi</th>
                                    <th>Jadwal Pelaksanaan</th>
                                    <th class="text-end pe-4">Pengaturan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; while($e=$exams->fetch_assoc()): 
                                    $now = new DateTime();
                                    $start = new DateTime($e['start_time']);
                                    $end = new DateTime($e['end_time']);
                                    
                                    if ($now < $start) {
                                        $status = '<span class="status-badge bg-warning-subtle text-warning">Mendatang</span>';
                                    } elseif ($now > $end) {
                                        $status = '<span class="status-badge bg-secondary-subtle text-secondary">Selesai</span>';
                                    } else {
                                        $status = '<span class="status-badge bg-success-subtle text-success">Berlangsung</span>';
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-bold"><?= $i++ ?></td>
                                    <td>
                                        <span class="exam-title"><?= htmlspecialchars($e['title']) ?></span>
                                        <?= $status ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-hourglass-split text-muted me-2"></i>
                                            <span class="fw-medium"><?= intval($e['duration']) ?> menit</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-info">
                                            <div class="mb-1"><i class="bi bi-calendar-event me-2"></i>Mulai: <strong><?= date('d/m/Y H:i', strtotime($e['start_time'])) ?></strong></div>
                                            <div><i class="bi bi-calendar-x me-2"></i>Selesai: <strong><?= date('d/m/Y H:i', strtotime($e['end_time'])) ?></strong></div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a class="btn btn-action btn-outline-primary shadow-sm" href="blueprint.php?exam_id=<?= $e['id'] ?>">
                                            <i class="bi bi-ui-checks-grid me-2"></i>Konfigurasi Soal
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; if($exams->num_rows == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted italic">
                                        <i class="bi bi-calendar-x d-block fs-1 mb-2 opacity-25"></i>
                                        Belum ada jadwal ujian yang dibuat.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
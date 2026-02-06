<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"]!="admin" && $_SESSION["role"]!="guru")) {
    header("Location: ../auth/login.php"); exit;
}

include("../config/db.php");

$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
if(!$subject_id) die("Subject tidak valid.");

/* ================= QUERY ================= */
$sql = "
SELECT 
  e.id AS exam_id,
  e.title,
  u.id AS student_id,
  u.name AS student,
  COUNT(eq.question_id) AS total_soal,
  SUM(CASE WHEN q.type='pg' THEN 1 ELSE 0 END) AS pg_count,
  SUM(CASE WHEN q.type='esai' THEN 1 ELSE 0 END) AS esai_count,
  SUM(CASE 
      WHEN sa.score IS NULL THEN 0
      ELSE sa.score
  END) AS total_score
FROM exams e
JOIN exam_questions eq ON eq.exam_id = e.id
JOIN questions q ON q.id = eq.question_id
LEFT JOIN student_answers sa 
   ON sa.exam_id = e.id 
   AND sa.question_id = q.id
LEFT JOIN users u 
   ON u.id = sa.student_id
WHERE e.subject_id = ?
GROUP BY e.id, u.id
ORDER BY e.start_time DESC, u.name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$subject_id);
$stmt->execute();
$rs = $stmt->get_result();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Nilai - Sistem Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        body {
            background-color: #f8f9fc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #5a5c69;
        }
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.25rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .table thead th {
            background-color: #f8f9fc;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05rem;
            font-weight: 700;
            border-top: none;
        }
        .table tbody tr:hover {
            background-color: #f1f4f9;
        }
        .badge-baik { background-color: rgba(28, 200, 138, 0.1); color: #1cc88a; border: 1px solid #1cc88a; }
        .badge-sedang { background-color: rgba(246, 194, 62, 0.1); color: #f6c23e; border: 1px solid #f6c23e; }
        .badge-kurang { background-color: rgba(231, 74, 59, 0.1); color: #e74a3b; border: 1px solid #e74a3b; }
        
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        
        @media print {
            .noprint, .btn, .breadcrumb { display: none !important; }
            body { background-color: #fff; }
            .card { box-shadow: none; border: 1px solid #e3e6f0; }
            .container { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>

<body>
<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="noprint">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard/admin.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Laporan Nilai</li>
        </ol>
    </nav>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Hasil Nilai</h1>
        <div class="noprint">
            <button class="btn btn-primary shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Data Perolehan Nilai Siswa</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Ujian</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">Skor Akhir</th>
                            <th class="text-center">Detail (PG/Esai)</th>
                            <th>Persentase</th>
                            <th class="text-center">Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $rs->fetch_assoc()): 
                            $total_soal  = $r['total_soal']; 
                            $total_score = $r['total_score'];
                            $persen = ($total_soal > 0) ? round(($total_score / $total_soal) * 100, 1) : 0;
                            if ($persen > 100) $persen = 100;

                            if($persen >= 80) {
                                $level = "Baik"; $cls = "badge-baik"; $bar = "bg-success";
                            } elseif($persen >= 60) {
                                $level = "Sedang"; $cls = "badge-sedang"; $bar = "bg-warning";
                            } else {
                                $level = "Kurang"; $cls = "badge-kurang"; $bar = "bg-danger";
                            }
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($r['title']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-2 text-primary">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <?= htmlspecialchars($r['student'] ?? 'Tidak ada data') ?>
                                </div>
                            </td>
                            <td class="text-center fw-bold text-primary">
                                <?= $total_score ?> <span class="text-muted small">/ <?= $total_soal ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><i class="bi bi-list-check me-1"></i>PG: <?= $r['pg_count'] ?></span>
                                <span class="badge bg-light text-dark border"><i class="bi bi-pencil-square me-1"></i>E: <?= $r['esai_count'] ?></span>
                            </td>
                            <td style="min-width: 150px;">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="progress me-2">
                                            <div class="progress-bar <?= $bar ?>" role="progressbar" style="width: <?= $persen ?>%"></div>
                                        </div>
                                    </div>
                                    <span class="small fw-bold"><?= $persen ?>%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $cls ?> px-3 py-2">
                                    <?= $level ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 noprint">
            <a class="btn btn-outline-secondary btn-sm" href="../dashboard/admin.php?subject_id=<?= $subject_id ?>">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
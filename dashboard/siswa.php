<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "siswa") {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

// Set timezone ke WITA (Sulawesi Barat)
date_default_timezone_set('Asia/Makassar');
$now = date('Y-m-d H:i:s');

// Ambil ujian aktif
$stmt = $conn->prepare("
    SELECT e.*, s.name AS subject_name
    FROM exams e
    JOIN subjects s ON s.id = e.subject_id
    WHERE e.start_time <= ? AND e.end_time >= ?
    ORDER BY e.start_time ASC
");
$stmt->bind_param("ss", $now, $now);
$stmt->execute();
$exams = $stmt->get_result();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Siswa - Platform Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg-soft: #f8fafc;
        }
        body { 
            background-color: var(--bg-soft); 
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }
        .header-section {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 3rem 0 6rem;
            color: white;
            margin-bottom: -4rem;
        }
        .exam-card {
            border: none;
            border-radius: 1.25rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            background: white;
            overflow: hidden;
        }
        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.1);
        }
        .subject-badge {
            background-color: #eef2ff;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            display: inline-block;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
        }
        .timer-box {
            background-color: #fff1f2;
            color: #e11d48;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-start {
            background-color: var(--primary);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-start:hover {
            background-color: var(--primary-dark);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            background: white;
            border-radius: 1.5rem;
            border: 2px dashed #e2e8f0;
        }
    </style>
</head>
<body>

<div class="header-section">
    <div class="container text-center">
        <h2 class="fw-bold mb-1">Selamat Datang, Siswa!</h2>
        <p class="opacity-75">Silakan cek jadwal ujian aktif Anda di bawah ini.</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h4 class="mb-4 text-white position-relative" style="z-index: 2;">
                <i class="bi bi- lightning-charge-fill me-2"></i>Ujian Berlangsung
            </h4>

            <?php if($exams->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while($exam = $exams->fetch_assoc()): 
                        $start_time = date('H:i', strtotime($exam['start_time']));
                        $end_time   = date('H:i', strtotime($exam['end_time']));
                    ?>
                        <div class="col-12">
                            <div class="exam-card p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <span class="subject-badge"><?= htmlspecialchars($exam['subject_name']) ?></span>
                                        <h4 class="fw-bold mb-2"><?= htmlspecialchars($exam['title'] ?? $exam['name']) ?></h4>
                                        <div class="text-muted small d-flex flex-wrap gap-3">
                                            <span><i class="bi bi-clock me-1"></i> <?= $start_time ?> – <?= $end_time ?> WITA</span>
                                            <span><i class="bi bi-journal-text me-1"></i> Sesi Aktif</span>
                                        </div>
                                    </div>
                                    <div class="col-md-5 mt-3 mt-md-0">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="timer-box" id="container-timer-<?= $exam['id'] ?>">
                                                <i class="bi bi-stopwatch-fill"></i>
                                                Sisa: <span id="timer-<?= $exam['id'] ?>">--:--:--</span>
                                            </div>
                                            <a href="../ujian/take.php?exam_id=<?= $exam['id'] ?>" class="btn-start">
                                                Mulai Ujian <i class="bi bi-arrow-right-short fs-4"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            (function() {
                                const endTime = new Date("<?= $exam['end_time'] ?>").getTime();
                                const timerEl = document.getElementById("timer-<?= $exam['id'] ?>");
                                
                                function updateTimer() {
                                    const now = new Date().getTime();
                                    const distance = endTime - now;
                                    
                                    if(distance < 0){
                                        timerEl.innerHTML = "Waktu Habis";
                                        return;
                                    }
                                    
                                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000*60*60));
                                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000*60));
                                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                                    
                                    timerEl.innerHTML = 
                                        (hours > 0 ? hours + "j " : "") + 
                                        minutes + "m " + seconds + "s";
                                }
                                updateTimer();
                                setInterval(updateTimer, 1000);
                            })();
                        </script>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="100" class="mb-3 opacity-50" alt="No Exams">
                    <h5 class="fw-bold text-muted">Belum Ada Ujian Aktif</h5>
                    <p class="text-muted small">Hubungi guru pengampu jika Anda merasa seharusnya ada ujian saat ini.</p>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-5">
                <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">
                    <i class="bi bi-box-arrow-left me-2"></i>Keluar Aplikasi
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
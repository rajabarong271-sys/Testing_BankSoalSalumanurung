<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "siswa") {
    header("Location: ../auth/login.php");
    exit;
}

include("../config/db.php");

// Zona waktu WITA (Sulawesi Barat)
date_default_timezone_set('Asia/Makassar');
$now = date('Y-m-d H:i:s');

// Validasi exam_id
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
if ($exam_id <= 0) die("Ujian tidak ditemukan.");

// Ambil data ujian
$stmt = $conn->prepare("SELECT * FROM exams WHERE id=?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
if (!$exam) die("Data ujian tidak ditemukan.");

// Cek waktu
$start = strtotime($exam['start_time']);
$end   = strtotime($exam['end_time']);
$nowTS = strtotime($now);
if ($nowTS < $start) die("Ujian belum dimulai.");
if ($nowTS > $end) die("Waktu ujian telah habis.");

// Ambil soal (A–E)
$stmt = $conn->prepare("
    SELECT q.id, q.question, q.type,
           q.option_a, q.option_b, q.option_c,
           q.option_d, q.option_e
    FROM questions q
    JOIN exam_questions eq ON eq.question_id=q.id
    WHERE eq.exam_id=?
    ORDER BY q.id ASC
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$questions = $stmt->get_result();

// Jawaban siswa yang sudah tersimpan sebelumnya (jika ada)
$answers = [];
$q_ans = $conn->prepare("
    SELECT question_id, answer
    FROM student_answers
    WHERE student_id=? AND exam_id=?
");
$q_ans->bind_param("ii", $_SESSION['user_id'], $exam_id);
$q_ans->execute();
$r_ans = $q_ans->get_result();
while ($d = $r_ans->fetch_assoc()) {
    $answers[$d['question_id']] = $d['answer'];
}

// Simpan jawaban saat tombol selesai ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    if (isset($_POST['answer'])) {
        foreach ($_POST['answer'] as $qid => $ans) {
            $qid = intval($qid);
            $ans = trim($ans);

            $c = $conn->prepare("
                SELECT id FROM student_answers
                WHERE student_id=? AND exam_id=? AND question_id=?
            ");
            $c->bind_param("iii", $_SESSION['user_id'], $exam_id, $qid);
            $c->execute();

            if ($c->get_result()->num_rows > 0) {
                $u = $conn->prepare("
                    UPDATE student_answers
                    SET answer=?
                    WHERE student_id=? AND exam_id=? AND question_id=?
                ");
                $u->bind_param("siii", $ans, $_SESSION['user_id'], $exam_id, $qid);
                $u->execute();
            } else {
                $i = $conn->prepare("
                    INSERT INTO student_answers (student_id, exam_id, question_id, answer)
                    VALUES (?,?,?,?)
                ");
                $i->bind_param("iiis", $_SESSION['user_id'], $exam_id, $qid, $ans);
                $i->execute();
            }
        }
    }

    // Alihkan ke halaman sukses atau dashboard
    echo "<script>alert('Ujian berhasil dikirim!'); window.location.href='../dashboard/siswa.php';</script>";
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($exam['title']) ?> - Sesi Ujian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --bg-page: #f8fafc;
            --border: #e2e8f0;
        }
        body {
            background-color: var(--bg-page);
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }
        .navbar-exam {
            background: white;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .timer-badge {
            background: #fff1f2;
            color: #e11d48;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #fecdd3;
        }
        .question-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .question-number {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .form-check {
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            cursor: pointer;
        }
        .form-check:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }
        .form-check-input:checked + .form-check-label {
            color: var(--primary);
            font-weight: 600;
        }
        .form-check-input:checked ~ .form-check {
            border-color: var(--primary);
            background-color: #eef2ff;
        }
        .btn-finish {
            background: #10b981;
            border: none;
            padding: 1rem;
            border-radius: 0.75rem;
            font-weight: 700;
            color: white;
            transition: all 0.3s;
        }
        .btn-finish:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        textarea.form-control {
            border-radius: 0.75rem;
            padding: 1rem;
            border: 1px solid var(--border);
        }
        textarea.form-control:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            border-color: var(--primary);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-exam py-3 mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-muted small d-block">Sedang Mengerjakan:</span>
            <strong class="h5 mb-0"><?= htmlspecialchars($exam['title']) ?></strong>
        </div>
        <div class="timer-badge">
            <i class="bi bi-clock-fill"></i>
            <span id="timer">--:--:--</span>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form method="post" id="examForm">
                <input type="hidden" name="submit_exam" value="1">

                <?php 
                $counter = 1;
                while ($q = $questions->fetch_assoc()): 
                ?>
                    <div class="question-card">
                        <div class="question-number"><?= $counter++ ?></div>
                        <div class="mb-4 fw-medium" style="font-size: 1.1rem; line-height: 1.6;">
                            <?= nl2br(htmlspecialchars($q['question'])) ?>
                        </div>

                        <?php if (strtolower($q['type']) == 'pg'): ?>
                            <?php
                            $opsi = [
                                'A' => $q['option_a'],
                                'B' => $q['option_b'],
                                'C' => $q['option_c'],
                                'D' => $q['option_d'],
                                'E' => $q['option_e']
                            ];
                            foreach ($opsi as $k => $v):
                                if (!empty($v)):
                                    $ans_id = "ans_" . $q['id'] . "_" . $k;
                                    $is_checked = (isset($answers[$q['id']]) && $answers[$q['id']] == $k) ? 'checked' : '';
                            ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="answer[<?= $q['id'] ?>]" 
                                           id="<?= $ans_id ?>"
                                           value="<?= $k ?>" <?= $is_checked ?>>
                                    <label class="form-check-label d-block cursor-pointer" for="<?= $ans_id ?>">
                                        <span class="me-2 fw-bold"><?= $k ?>.</span> <?= htmlspecialchars($v) ?>
                                    </label>
                                </div>
                            <?php 
                                endif; 
                            endforeach; 
                            ?>

                        <?php else: ?>
                            <div class="mt-2">
                                <label class="form-label small text-muted">Jawaban Essay:</label>
                                <textarea class="form-control" 
                                          name="answer[<?= $q['id'] ?>]" 
                                          rows="4" 
                                          placeholder="Tuliskan jawaban lengkap Anda di sini..."><?= htmlspecialchars($answers[$q['id']] ?? '') ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>

                <div class="card p-4 border-0 shadow-sm rounded-4 text-center mt-5">
                    <h5 class="fw-bold mb-3">Selesai Mengerjakan?</h5>
                    <p class="text-muted small mb-4">Pastikan semua pertanyaan telah dijawab dengan benar sebelum mengirimkan ujian.</p>
                    <button type="submit" class="btn btn-finish w-100">
                        <i class="bi bi-send-fill me-2"></i> Kirim Jawaban Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const endTime = new Date("<?= $exam['end_time'] ?>").getTime();
    const timerDisplay = document.getElementById("timer");

    function updateTimer() {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance <= 0) {
            timerDisplay.innerHTML = "WAKTU HABIS";
            // Submit otomatis jika waktu habis
            document.getElementById("examForm").submit();
            return;
        }

        const h = Math.floor(distance / (1000 * 60 * 60));
        const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((distance % (1000 * 60)) / 1000);

        timerDisplay.innerHTML = 
            (h > 0 ? h + "j " : "") + 
            (m < 10 ? "0" + m : m) + "m " + 
            (s < 10 ? "0" + s : s) + "s";
    }

    setInterval(updateTimer, 1000);
    updateTimer();

    // Mencegah penutupan tab secara tidak sengaja
    window.onbeforeunload = function() {
        return "Apakah Anda yakin ingin meninggalkan ujian? Jawaban Anda mungkin belum tersimpan.";
    };

    // Hilangkan peringatan saat submit form
    document.getElementById("examForm").onsubmit = function() {
        window.onbeforeunload = null;
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
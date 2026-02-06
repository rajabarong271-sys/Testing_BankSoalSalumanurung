<?php
session_start();
include("../config/db.php");

// 1. UPDATE PROTEKSI HALAMAN
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'guru')) {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil parameter dari URL
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$exam_id    = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Pastikan redirect default menyertakan subject_id
$redirect_default = "nilai_index.php?subject_id=" . $subject_id; 

if ($student_id == 0 || $exam_id == 0) {
    header("Location: $redirect_default"); 
    exit;
}

// 2. Logika Simpan Skor Satuan (Esai)
if (isset($_POST['score'])) {
    $score = floatval($_POST['score']);
    $answer_id = intval($_POST['answer_id']);

    $stmt = $conn->prepare("UPDATE student_answers SET score=? WHERE id=?");
    $stmt->bind_param("di", $score, $answer_id);
    $stmt->execute();
    $stmt->close();
    
    // Refresh halaman agar parameter URL tetap terjaga setelah POST score
    header("Location: periksa_jawaban.php?student_id=$student_id&exam_id=$exam_id&subject_id=$subject_id");
    exit;
}

// 3. Logika Finalisasi
if (isset($_POST['submit_nilai'])) {
    // Pastikan variabel redirect_default yang digunakan menyertakan subject_id terbaru
    $target_url = "nilai_index.php?subject_id=" . $subject_id;
    echo "<script>
        alert('Seluruh nilai berhasil diperbarui!');
        window.location.href = '$target_url';
    </script>";
    exit;
}

// Ambil Info Siswa & Ujian
$info_query = $conn->query("
    SELECT u.name, e.title
    FROM users u
    JOIN exams e ON e.id=$exam_id
    WHERE u.id=$student_id
");
$info = $info_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periksa Jawaban - <?= htmlspecialchars($info['name'] ?? 'Siswa') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            padding: 40px 20px;
        }

        .container { max-width: 800px; margin: 0 auto; }

        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: var(--white);
            color: var(--text-main);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid var(--border);
            transition: all 0.2s;
            gap: 8px;
        }

        .btn-back:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateX(-4px);
        }

        .header-card {
            background: var(--white);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 4px; }
        .header-info p { font-size: 0.875rem; color: var(--text-muted); }

        .question-card {
            background: var(--white);
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .q-number {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            display: block;
        }

        .q-text { font-weight: 600; margin-bottom: 16px; font-size: 1rem; color: #334155; }

        .answer-box {
            background: #f1f5f9;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #cbd5e1;
            margin-bottom: 16px;
        }

        .answer-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block; }
        .answer-text { font-size: 0.935rem; color: var(--text-main); white-space: pre-wrap; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.813rem;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .scoring-group { display: flex; gap: 10px; margin-top: 16px; }
        .btn-score {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--white);
            cursor: pointer;
            font-size: 0.813rem;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .btn-score.active[value="0"] { background: var(--danger); color: white; border-color: var(--danger); }
        .btn-score.active[value="1"] { background: var(--warning); color: white; border-color: var(--warning); }
        .btn-score.active[value="2"] { background: var(--success); color: white; border-color: var(--success); }

        .footer-action {
            margin-top: 40px;
            text-align: center;
            padding-bottom: 60px;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 14px 40px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="nav-header">
        <a href="<?= $redirect_default ?>" class="btn-back">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Daftar
        </a>
    </div>

    <div class="header-card">
        <div class="header-info">
            <p>Pemeriksaan Hasil Ujian</p>
            <h2><?= htmlspecialchars($info['name'] ?? 'Siswa') ?></h2>
            <p><?= htmlspecialchars($info['title'] ?? 'Judul Ujian') ?></p>
        </div>
        <div>
            <span class="badge" style="background:#eef2ff; color:#4f46e5; text-transform:uppercase;">
                Role: <?= htmlspecialchars($_SESSION['role']) ?>
            </span>
        </div>
    </div>

    <?php
    $q = $conn->query("
        SELECT sa.id, sa.answer, sa.score,
                q.question, q.type, q.answer_key
        FROM student_answers sa
        JOIN questions q ON sa.question_id=q.id
        WHERE sa.student_id=$student_id AND sa.exam_id=$exam_id
    ");

    $no = 1;
    if ($q && $q->num_rows > 0):
        while($row = $q->fetch_assoc()) :
        ?>
            <div class="question-card">
                <span class="q-number">Soal <?= $no ?> (<?= strtoupper($row['type']) ?>)</span>
                <div class="q-text"><?= $row['question'] ?></div>
                
                <div class="answer-box">
                    <span class="answer-label">Jawaban Siswa:</span>
                    <div class="answer-text"><?= htmlspecialchars($row['answer']) ?></div>
                </div>

                <?php if($row['type'] == "pg") : 
                    $is_correct = (trim($row['answer']) == trim($row['answer_key']));
                    $pg_score = $is_correct ? 1 : 0;
                    
                    if ($row['score'] === null || $row['score'] != $pg_score) {
                        $conn->query("UPDATE student_answers SET score=$pg_score WHERE id=".$row['id']);
                    }
                    
                    if($is_correct) : ?>
                        <span class="badge badge-success">✔ Benar (Skor: 1)</span>
                    <?php else : ?>
                        <span class="badge badge-danger">✘ Salah (Skor: 0)</span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px; background: #fff1f2; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                            Kunci Jawaban: <strong><?= htmlspecialchars($row['answer_key']) ?></strong>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($row['type'] == "esai") : 
                    $current = $row['score'];
                ?>
                    <form method='post' action="?student_id=<?= $student_id ?>&exam_id=<?= $exam_id ?>&subject_id=<?= $subject_id ?>" class='scoring-group'>
                        <input type='hidden' name='answer_id' value='<?= $row['id'] ?>'>
                        <button type="submit" name="score" value="0" class="btn-score <?= ($current !== null && round($current) == 0) ? 'active' : '' ?>">
                            <span>0</span><small>Salah</small>
                        </button>
                        <button type="submit" name="score" value="1" class="btn-score <?= ($current !== null && round($current) == 1) ? 'active' : '' ?>">
                            <span>1</span><small>Setengah</small>
                        </button>
                        <button type="submit" name="score" value="2" class="btn-score <?= ($current !== null && round($current) == 2) ? 'active' : '' ?>">
                            <span>2</span><small>Benar</small>
                        </button>
                    </form>

                    <?php if($row['score'] !== null) : ?>
                        <div style="margin-top:12px; font-size:0.8rem; color:var(--success); font-weight:600; display:flex; align-items:center; gap:5px;">
                             <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                             Skor tersimpan: <?= round($row['score']) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php 
        $no++;
        endwhile; 
    else:
        echo "<p style='text-align:center; padding:20px; background:#fff; border-radius:12px;'>Tidak ada data jawaban untuk siswa ini.</p>";
    endif;
    ?>

    <div class="footer-action">
        <form method="post" action="?student_id=<?= $student_id ?>&exam_id=<?= $exam_id ?>&subject_id=<?= $subject_id ?>">
            <button type="submit" name="submit_nilai" class="btn-submit">
                Simpan & Selesai Memeriksa
            </button>
        </form>
    </div>
</div>

</body>
</html>
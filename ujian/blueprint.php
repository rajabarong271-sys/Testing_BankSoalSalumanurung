<?php
session_start();
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"], ["admin", "guru"])) { 
    header("Location: ../auth/login.php"); 
    exit; 
}

include("../config/db.php");

// Set timezone Sulawesi Barat (WITA)
date_default_timezone_set('Asia/Makassar'); 
$now = date('Y-m-d H:i:s');

// Validasi exam_id dari URL
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
if ($exam_id <= 0) {
    die("Ujian tidak ditemukan atau parameter tidak valid.");
}

// Ambil data ujian
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
if (!$exam) {
    die("Data ujian tidak ditemukan.");
}

$subject_id = intval($exam['subject_id']);

// Jika ada submit POST, simpan blueprint soal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Hapus blueprint lama
    $del = $conn->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
    $del->bind_param("i", $exam_id);
    $del->execute();

    // 2. Simpan blueprint baru
    if (!empty($_POST['qid'])) {
        $stmt = $conn->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
        foreach ($_POST['qid'] as $qid) {
            $qid = intval($qid);
            $stmt->bind_param("ii", $exam_id, $qid);
            $stmt->execute();
        }
    }

    // 3. Aktifkan ujian dengan waktu WITA
    $duration = intval($exam['duration']); // durasi dalam menit
    $start_time = $now;
    $end_time = date('Y-m-d H:i:s', strtotime("+$duration minutes", strtotime($start_time)));

    $upd = $conn->prepare("UPDATE exams SET start_time = ?, end_time = ? WHERE id = ?");
    $upd->bind_param("ssi", $start_time, $end_time, $exam_id);
    $upd->execute();

    // 4. Redirect dengan notifikasi sukses
    echo "<script>
        alert('Blueprint soal berhasil disimpan dan ujian telah diaktifkan!');
        window.location.href='../dashboard/siswa.php';
    </script>";
    exit;
}

// Ambil semua soal berdasarkan mata pelajaran
$q = $conn->prepare("SELECT id, question, type FROM questions WHERE subject_id = ? ORDER BY id DESC");
$q->bind_param("i", $subject_id);
$q->execute();
$questions = $q->get_result();

// Ambil soal yang sudah dipilih di blueprint
$selQ = $conn->prepare("SELECT question_id FROM exam_questions WHERE exam_id = ?");
$selQ->bind_param("i", $exam_id);
$selQ->execute();
$selectedQ = $selQ->get_result();
$sel = [];
while ($r = $selectedQ->fetch_assoc()) {
    $sel[$r['question_id']] = true;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Blueprint Soal Ujian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h4 class="mb-3">Pilih Soal untuk: <strong><?= htmlspecialchars($exam['title']) ?></strong></h4>

    <form method="post">
        <div class="list-group">
            <?php while ($row = $questions->fetch_assoc()): ?>
                <label class="list-group-item">
                    <input class="form-check-input me-1" type="checkbox" name="qid[]" 
                           value="<?= $row['id'] ?>" <?= isset($sel[$row['id']]) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($row['question']) ?> 
                    <small class="text-muted">(<?= htmlspecialchars($row['type']) ?>)</small>
                </label>
            <?php endwhile; ?>
        </div>

        <button class="btn btn-primary mt-3">💾 Simpan Blueprint & Aktifkan Ujian</button>
    </form>

    <a class="btn btn-secondary mt-3" href="index.php?subject_id=<?= $subject_id ?>">⬅ Kembali</a>
</div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"]!="siswa") { header("Location: ../auth/login.php"); exit; }
include("../config/db.php");
$student_id=$_SESSION['user_id']; $exam_id=intval($_GET['exam_id'] ?? 0);
$q=$conn->prepare("SELECT e.title, s.name as subject_name FROM exams e JOIN subjects s ON s.id=e.subject_id WHERE e.id=?");
$q->bind_param("i",$exam_id); $q->execute(); $exam=$q->get_result()->fetch_assoc();
$answers=$conn->prepare("SELECT sa.*, q.question, q.type, q.answer_key FROM student_answers sa JOIN questions q ON q.id=sa.question_id WHERE sa.exam_id=? AND sa.student_id=?");
$answers->bind_param("ii",$exam_id,$student_id); $answers->execute(); $rs=$answers->get_result();
$total=0; $count=0;
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Hasil Ujian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container mt-4">
<h4>Hasil: <?= htmlspecialchars($exam['title']??'Ujian') ?> (<?= htmlspecialchars($exam['subject_name']??'') ?>)</h4>
<table class="table table-bordered bg-white"><thead><tr><th>#</th><th>Pertanyaan</th><th>Jawaban</th><th>Kunci</th><th>Skor</th></tr></thead><tbody>
<?php $i=1; while($r=$rs->fetch_assoc()): $total += floatval($r['score']); $count++; ?>
<tr><td><?= $i++ ?></td><td><?= nl2br(htmlspecialchars($r['question'])) ?></td>
<td><?= nl2br(htmlspecialchars($r['answer'])) ?></td>
<td><?= $r['type']=='pg' ? htmlspecialchars($r['answer_key']) : '-' ?></td>
<td><?= is_null($r['score'])?'-':$r['score'] ?></td></tr>
<?php endwhile; ?>
</tbody></table>
<p><strong>Total Skor:</strong> <?= $total ?> / <?= $count ?> (<?= $count>0? round(($total/$count)*100,2):0 ?>%)</p>
<a class="btn btn-secondary" href="../dashboard/siswa.php">Kembali</a>
</div></body></html>

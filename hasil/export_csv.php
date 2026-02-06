<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"]!="admin" && $_SESSION["role"]!="guru")) { http_response_code(403); exit; }
include("../config/db.php");
$subject_id=isset($_GET['subject_id'])?intval($_GET['subject_id']):0;
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="laporan_subject_'+strval($subject_id)+'.csv"');
$out=fopen('php://output','w');
fputcsv($out, ['exam_title','student','score','total_questions','percentage']);
$sql = "SELECT e.title, u.name as student,
SUM(CASE WHEN sa.score IS NULL THEN 0 ELSE sa.score END) AS total_score,
COUNT(sa.id) AS total_q
FROM exams e
JOIN student_answers sa ON sa.exam_id=e.id
JOIN users u ON u.id=sa.student_id
JOIN questions q ON q.id=sa.question_id
WHERE e.subject_id=?
GROUP BY e.id, u.id";
$stmt=$conn->prepare($sql); $stmt->bind_param("i",$subject_id); $stmt->execute(); $rs=$stmt->get_result();
while($r=$rs->fetch_assoc()){
  $pct = $r['total_q']>0 ? round(($r['total_score']/$r['total_q'])*100,2) : 0;
  fputcsv($out, [$r['title'],$r['student'],$r['total_score'],$r['total_q'],$pct]);
}
fclose($out);

<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"]!="siswa") { header("Location: ../auth/login.php"); exit; }
include("../config/db.php");
$student_id=$_SESSION['user_id'];
$exam_id=intval($_POST['exam_id'] ?? 0); if(!$exam_id) die("Ujian tidak valid.");
$answers = $_POST['answer'] ?? [];
foreach($answers as $qid=>$ans){
  $qid=intval($qid);
  // Upsert answer
  $stmt=$conn->prepare("INSERT INTO student_answers(exam_id,student_id,question_id,answer,score) VALUES (?,?,?,?,NULL) ON DUPLICATE KEY UPDATE answer=VALUES(answer)");
  $stmt->bind_param("iiis",$exam_id,$student_id,$qid,$ans); $stmt->execute();
  // Auto-grade for PG
  $q=$conn->query("SELECT type, answer_key FROM questions WHERE id=".$qid)->fetch_assoc();
  if($q && $q['type']=='pg'){
    $correct = strtoupper(trim($q['answer_key']))==strtoupper(trim($ans)) ? 1 : 0;
    $score = $correct ? 1.0 : 0.0; // skor per soal PG = 1
    $stmt2=$conn->prepare("UPDATE student_answers SET score=? WHERE exam_id=? AND student_id=? AND question_id=?");
    $stmt2->bind_param("diii",$score,$exam_id,$student_id,$qid); $stmt2->execute();
  }
}
// redirect to result page
header("Location: ../hasil/student.php?exam_id=".$exam_id);

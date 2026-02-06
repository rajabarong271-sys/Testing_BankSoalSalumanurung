<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"]!="admin" && $_SESSION["role"]!="guru")) { http_response_code(403); exit; }
include("../config/db.php");
$subject_id=isset($_GET['subject_id'])?intval($_GET['subject_id']):0;
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="bank_soal_subject_'+strval($subject_id)+'.csv"');
$out=fopen('php://output','w');
fputcsv($out, ['type','question','option_a','option_b','option_c','option_d','option_e','answer_key']);
$q=$conn->prepare("SELECT type,question,option_a,option_b,option_c,option_d,answer_key FROM questions WHERE subject_id=?");
$q->bind_param("i",$subject_id); $q->execute(); $rs=$q->get_result();
while($r=$rs->fetch_assoc()){ fputcsv($out, $r); }
fclose($out);

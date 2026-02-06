<?php
session_start();
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"]!="admin" && $_SESSION["role"]!="guru")) { header("Location: ../auth/login.php"); exit; }
include("../config/db.php");
$user_id=$_SESSION['user_id']; $role=$_SESSION['role'];
$subject_id=isset($_GET['subject_id'])?intval($_GET['subject_id']):0; if(!$subject_id){ die("Subject tidak valid."); }
if ($role==='guru'){ $chk=$conn->prepare("SELECT 1 FROM teacher_subjects WHERE teacher_id=? AND subject_id=?"); $chk->bind_param("ii",$user_id,$subject_id); $chk->execute(); if(!$chk->get_result()->num_rows){ die("Tidak punya akses ke mapel ini."); } }

$info="";
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv'])) {
  if (is_uploaded_file($_FILES['csv']['tmp_name'])) {
    $fh=fopen($_FILES['csv']['tmp_name'],'r');
    $count=0;
    // header: type,question,option_a,option_b,option_c,option_d,answer_key
    while(($row=fgetcsv($fh))!==false){
      if ($count==0 && strtolower($row[0])==='type') { $count++; continue; }
      $type=$row[0]; $question=$row[1]; $a=$row[2]??NULL; $b=$row[3]??NULL; $c=$row[4]??NULL; $d=$row[5]??NULL; $e=$row[6]??NULL; $key=$row[7]??NULL;
      $stmt=$conn->prepare("INSERT INTO questions(subject_id, created_by, type, question, option_a, option_b, option_c, option_d, option_e, answer_key) VALUES (?,?,?,?,?,?,?,?,?,?)");
      $stmt->bind_param("iisssssss",$subject_id,$user_id,$type,$question,$a,$b,$c,$d,$d,$e,$key); $stmt->execute(); $count++;
    }
    fclose($fh);
    $info="Import berhasil: {$count} baris.";
  } else { $info="Gagal upload file."; }
}
if (isset($_GET['export']) && $_GET['export']=='csv') {
  header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="bank_soal_subject_'+strval($subject_id)+'.csv"');
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Import/Export Soal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container mt-4">
<h4>Import/Export Soal (CSV) - Subject ID <?= $subject_id ?></h4>
<?php if($info): ?><div class="alert alert-info"><?= htmlspecialchars($info) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-md-6"><div class="card"><div class="card-body">
    <h5>Import CSV</h5>
    <p>Format header: <code>type,question,option_a,option_b,option_c,option_d,option_e,answer_key</code></p>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="csv" accept=".csv" class="form-control mb-2" required>
      <button class="btn btn-primary">Import</button>
    </form>
  </div></div></div>
  <div class="col-md-6"><div class="card"><div class="card-body">
    <h5>Export CSV</h5>
    <a class="btn btn-success" href="export_csv.php?subject_id=<?= $subject_id ?>">Download CSV</a>
  </div></div></div>
</div>
<a class="btn btn-secondary mt-3" href="../soal/index.php?subject_id=<?= $subject_id ?>">Kembali</a>
</div></body></html>

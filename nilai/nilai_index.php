<?php
session_start();
include("../config/db.php");

// 1. Cek hak akses
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'guru')) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// 2. Ambil filter dari URL
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$search_name = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// 3. Validasi: Jika Guru, pastikan dia hanya bisa melihat mapel yang diampunya
if ($role == 'guru') {
    $stmt_check = $conn->prepare("SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
    $stmt_check->bind_param("ii", $user_id, $subject_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    
    // Jika mencoba akses mapel yang bukan miliknya (dan bukan 'Semua')
    if ($subject_id > 0 && $res_check->num_rows == 0) {
        die("<div style='padding:20px; color:red; font-family:sans-serif;'>❌ Anda tidak memiliki otoritas untuk mengakses mata pelajaran ini.</div>");
    }
}

// Ambil nama mata pelajaran untuk judul
$subject_name = "Semua Mata Pelajaran";
if ($subject_id > 0) {
    $stmt_subj = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
    $stmt_subj->bind_param("i", $subject_id);
    $stmt_subj->execute();
    $res_subj = $stmt_subj->get_result();
    if ($row_subj = $res_subj->fetch_assoc()) {
        $subject_name = $row_subj['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian - <?= htmlspecialchars($subject_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #e0e7ff;
            --bg-body: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); line-height: 1.6; padding-bottom: 50px; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 30px 0 20px 0; }
        .btn-back { 
            display: inline-flex; align-items: center; padding: 10px 18px; background: var(--white); 
            color: var(--text-main); text-decoration: none; border-radius: 12px; font-size: 0.875rem; 
            font-weight: 600; border: 1px solid var(--border); transition: all 0.2s; gap: 8px; 
        }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); transform: translateX(-3px); }

        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 1.875rem; font-weight: 800; }
        .page-header p { color: var(--text-muted); }

        .filter-section {
            background: var(--white); padding: 20px; border-radius: 16px; margin-bottom: 25px;
            border: 1px solid var(--border); display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 200px; }
        .filter-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); }
        .filter-input { padding: 10px 15px; border-radius: 10px; border: 1px solid var(--border); outline: none; }
        .btn-filter { padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }

        .card { background: var(--white); border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04); overflow: hidden; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fafafa; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; padding: 20px 24px; border-bottom: 1px solid var(--border); text-align: left; }
        td { padding: 20px 24px; border-bottom: 1px solid var(--border); }

        .student-cell { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 36px; height: 36px; background: var(--primary-light); color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .exam-badge { background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
        .btn-action { background: var(--primary); color: white; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.875rem; transition: background 0.2s; }
        .btn-action:hover { background: var(--primary-hover); }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <a href="../dashboard/guru.php" class="btn-back">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Dashboard
        </a>
    </div>

    <div class="page-header">
        <h1>Pemeriksaan Jawaban</h1>
        <p>Menampilkan hasil kerja siswa untuk mata pelajaran: <strong><?= htmlspecialchars($subject_name) ?></strong></p>
    </div>

    <!-- Form Filter -->
    <form method="GET" class="filter-section">
        <div class="filter-group">
            <label class="filter-label">Mata Pelajaran</label>
            <select name="subject_id" class="filter-input">
                <option value="0">Semua Mata Pelajaran</option>
                <?php
                if ($role == 'guru') {
                    $res_mapel = $conn->prepare("SELECT s.id, s.name FROM subjects s JOIN teacher_subjects ts ON s.id = ts.subject_id WHERE ts.teacher_id = ?");
                    $res_mapel->bind_param("i", $user_id);
                    $res_mapel->execute();
                    $res_all_subj = $res_mapel->get_result();
                } else {
                    $res_all_subj = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
                }

                while($s = $res_all_subj->fetch_assoc()){
                    $sel = ($s['id'] == $subject_id) ? 'selected' : '';
                    echo "<option value='{$s['id']}' $sel>{$s['name']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Cari Nama Siswa</label>
            <input type="text" name="search" class="filter-input" placeholder="Nama siswa..." value="<?= htmlspecialchars($search_name) ?>">
        </div>
        <button type="submit" class="btn-filter">Filter Data</button>
    </form>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Nama Siswa</th>
                    <th>Ujian</th>
                    <th style="text-align: right;">Opsi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $sql = "SELECT 
                            u.id AS student_id, u.name AS student_name,
                            e.id AS exam_id, e.title AS exam_title
                        FROM student_answers sa
                        JOIN users u ON sa.student_id = u.id
                        JOIN exams e ON sa.exam_id = e.id
                        WHERE 1=1";
                
                if ($subject_id > 0) {
                    $sql .= " AND e.subject_id = $subject_id";
                } elseif ($role == 'guru') {
                    $sql .= " AND e.subject_id IN (SELECT subject_id FROM teacher_subjects WHERE teacher_id = $user_id)";
                }

                if (!empty($search_name)) {
                    $sql .= " AND u.name LIKE '%$search_name%'";
                }

                $sql .= " GROUP BY sa.student_id, sa.exam_id ORDER BY u.name ASC";
                
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $initial = strtoupper(substr($row['student_name'], 0, 1));
                        // PERBAIKAN: Menambahkan &subject_id pada link href di bawah ini
                        echo "
                        <tr>
                            <td>#{$no}</td>
                            <td>
                                <div class='student-cell'>
                                    <div class='avatar'>$initial</div>
                                    <div>".htmlspecialchars($row['student_name'])."</div>
                                </div>
                            </td>
                            <td><span class='exam-badge'>".htmlspecialchars($row['exam_title'])."</span></td>
                            <td style='text-align: right;'>
                                <a href='periksa_jawaban.php?student_id={$row['student_id']}&exam_id={$row['exam_id']}&subject_id=$subject_id' class='btn-action'>Buka Jawaban</a>
                            </td>
                        </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; padding:50px; color:#94a3b8;'>Belum ada jawaban yang masuk.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
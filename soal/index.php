<?php
session_start();

// Cek autentikasi
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] != "admin" && $_SESSION["role"] != "guru")) { 
    header("Location: ../auth/login.php"); 
    exit; 
}

include("../config/db.php");

$user_id = $_SESSION['user_id']; 
$role = $_SESSION["role"];

$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0; 
if(!$subject_id){ 
    die("Subject tidak valid."); 
}

// Verifikasi akses guru
if ($role === 'guru'){ 
    $chk = $conn->prepare("SELECT 1 FROM teacher_subjects WHERE teacher_id=? AND subject_id=?"); 
    $chk->bind_param("ii", $user_id, $subject_id); 
    $chk->execute(); 
    if(!$chk->get_result()->num_rows){ 
        die("Tidak punya akses ke mapel ini."); 
    } 
}

// Logic Tambah & Hapus tetap sama namun dengan penanganan UI yang lebih bersih
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])){
    $type = $_POST['type']; 
    $question = trim($_POST['question']);
    $a = $_POST['option_a'] ?? NULL; 
    $b = $_POST['option_b'] ?? NULL; 
    $c = $_POST['option_c'] ?? NULL; 
    $d = $_POST['option_d'] ?? NULL; 
    $e = $_POST['option_e'] ?? NULL; 
    $key = strtoupper($_POST['answer_key'] ?? '');
    
    $stmt = $conn->prepare("INSERT INTO questions(subject_id, created_by, type, question, option_a, option_b, option_c, option_d, option_e, answer_key) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iissssssss", $subject_id, $user_id, $type, $question, $a, $b, $c, $d, $e, $key); 
    
    if($stmt->execute()){
        header("Location: index.php?subject_id=".$subject_id."&success=1"); 
        exit;
    } else {
        $error = "Gagal menambah soal: " . $conn->error;
    }
}

if (isset($_GET['del'])){
    $qid = intval($_GET['del']);
    if($role === 'admin'){ 
        $stmt = $conn->prepare("DELETE FROM questions WHERE id=? AND subject_id=?");
        $stmt->bind_param("ii", $qid, $subject_id);
    } else { 
        $stmt = $conn->prepare("DELETE FROM questions WHERE id=? AND subject_id=? AND created_by=?");
        $stmt->bind_param("iii", $qid, $subject_id, $user_id);
    }
    if($stmt->execute()){
        header("Location: index.php?subject_id=".$subject_id."&success=2"); 
        exit;
    }
}

// Query data
$query_base = "SELECT q.*, u.name as creator FROM questions q LEFT JOIN users u ON u.id = q.created_by WHERE q.subject_id = ?";
if ($role !== 'admin') $query_base .= " AND q.created_by = ?";
$query_base .= " ORDER BY q.id DESC";

$stmt = $conn->prepare($query_base);
if ($role === 'admin') $stmt->bind_param("i", $subject_id);
else $stmt->bind_param("ii", $subject_id, $user_id);
$stmt->execute();
$q = $stmt->get_result();

$subject_stmt = $conn->prepare("SELECT name FROM subjects WHERE id = ?");
$subject_stmt->bind_param("i", $subject_id);
$subject_stmt->execute();
$subject = $subject_stmt->get_result()->fetch_assoc();
if(!$subject) die("Mapel tidak ditemukan.");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bank Soal - <?= htmlspecialchars($subject['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-color: #4e73df; }
        body { background-color: #f8f9fc; font-size: 0.95rem; }
        
        /* Layout Improvements */
        .card { border: none; border-radius: 12px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); margin-bottom: 1.5rem; }
        .card-header { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; font-weight: bold; }
        
        /* Question Content */
        .question-text { 
            max-height: 80px; 
            overflow: hidden; 
            position: relative; 
            transition: max-height 0.3s ease;
        }
        .question-text.expanded { max-height: 2000px; }
        .show-more { 
            color: var(--primary-color); 
            cursor: pointer; 
            font-weight: 600; 
            display: inline-block; 
            margin-top: 5px;
        }
        
        /* Mobile Optimization */
        @media (max-width: 768px) {
            .btn-responsive { width: 100%; margin-bottom: 0.5rem; }
            .header-flex { flex-direction: column; align-items: flex-start !important; }
            .header-flex .d-flex { width: 100%; margin-top: 1rem; }
            .table-mobile thead { display: none; }
            .table-mobile tr { display: block; margin-bottom: 1rem; border: 1px solid #e3e6f0; border-radius: 8px; background: #fff; }
            .table-mobile td { display: block; text-align: left !important; border: none; padding: 0.75rem; }
            .table-mobile td::before { content: attr(data-label); font-weight: bold; display: block; color: #858796; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 4px; }
            .table-mobile td:last-child { border-top: 1px solid #e3e6f0; background: #fcfcfc; }
        }

        .option-box { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
            gap: 10px; 
            background: #f1f3f9; 
            padding: 12px; 
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container-fluid container-lg py-4">
    <!-- Header Area -->
    <div class="header-flex d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800"><?= htmlspecialchars($subject['name']) ?></h1>
            <p class="text-muted mb-0">
                <span class="badge bg-primary"><?= $role === 'admin' ? 'Admin' : 'Guru' ?></span> 
                <?= htmlspecialchars($_SESSION['name']) ?>
            </p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a class="btn btn-outline-primary shadow-sm" href="import_export.php?subject_id=<?= $subject_id ?>">
                📥 Import/Export
            </a>
            <a class="btn btn-white border shadow-sm" href="../dashboard/admin.php">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
            <strong>Berhasil!</strong> <?= $_GET['success'] == 1 ? 'Soal telah ditambahkan.' : 'Soal telah dihapus.' ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Side -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="m-0">➕ Tambah Soal</h6>
                </div>
                <div class="card-body">
                    <form method="post" id="questionForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Jenis Soal</label>
                            <select name="type" class="form-select" id="typeSelect" onchange="toggleOptions()">
                                <option value="pg">Pilihan Ganda (PG)</option>
                                <option value="esai">Esai / Uraian</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Pertanyaan</label>
                            <textarea class="form-control" name="question" rows="4" required placeholder="Tulis soal..."></textarea>
                        </div>

                        <div id="pgOptions">
                            <?php foreach(['a', 'b', 'c', 'd', 'e'] as $opt): ?>
                            <div class="mb-2">
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold"><?= strtoupper($opt) ?></span>
                                    <input type="text" name="option_<?= $opt ?>" class="form-control" placeholder="Pilihan <?= strtoupper($opt) ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-primary">Kunci Jawaban</label>
                                <select name="answer_key" class="form-select border-primary text-primary fw-bold">
                                    <option value="">-- Pilih Kunci --</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm">
                            <i class="bi bi-save"></i> Simpan Soal
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List Side -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 text-primary">📋 Daftar Bank Soal</h6>
                    <span class="badge bg-secondary rounded-pill"><?= $q->num_rows ?> Soal</span>
                </div>
                <div class="card-body p-0">
                    <?php if($q->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-mobile">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" width="60">No</th>
                                    <th>Detail Soal</th>
                                    <th width="100">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while($row = $q->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Nomor" class="ps-3 fw-bold text-muted"><?= $i++ ?></td>
                                    <td data-label="Konten Soal">
                                        <div class="mb-1">
                                            <span class="badge <?= $row['type'] == 'pg' ? 'bg-info' : 'bg-warning' ?> mb-1">
                                                <?= strtoupper($row['type']) ?>
                                            </span>
                                            <small class="text-muted ms-2">Oleh: <?= htmlspecialchars($row['creator'] ?? 'System') ?></small>
                                        </div>
                                        
                                        <div class="question-text" id="q-<?= $row['id'] ?>">
                                            <?= nl2br(htmlspecialchars($row['question'])) ?>
                                            
                                            <?php if($row['type'] == 'pg'): ?>
                                            <div class="option-box small text-muted mt-2 border">
                                                <div><strong>A:</strong> <?= htmlspecialchars($row['option_a']) ?></div>
                                                <div><strong>B:</strong> <?= htmlspecialchars($row['option_b']) ?></div>
                                                <div><strong>C:</strong> <?= htmlspecialchars($row['option_c']) ?></div>
                                                <div><strong>D:</strong> <?= htmlspecialchars($row['option_d']) ?></div>
                                                <div><strong>E:</strong> <?= htmlspecialchars($row['option_e']) ?></div>
                                                <div class="text-primary fw-bold border-top pt-1 mt-1">Kunci: <?= $row['answer_key'] ?></div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="show-more" onclick="toggleQ(<?= $row['id'] ?>)">Selengkapnya...</span>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="d-flex gap-1">
                                            <a href="?subject_id=<?= $subject_id ?>&del=<?= $row['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger w-100"
                                               onclick="return confirm('Hapus soal ini?')">
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 80px; opacity: 0.3">
                        <p class="text-muted mt-3">Belum ada soal yang tersedia.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Form PG/Esai
function toggleOptions() {
    const type = document.getElementById('typeSelect').value;
    const pgBox = document.getElementById('pgOptions');
    pgBox.style.display = (type === 'pg') ? 'block' : 'none';
}

// Toggle Expand Question
function toggleQ(id) {
    const el = document.getElementById('q-' + id);
    const trigger = el.nextElementSibling;
    el.classList.toggle('expanded');
    trigger.textContent = el.classList.contains('expanded') ? 'Sembunyikan' : 'Selengkapnya...';
}

// Inisialisasi tampilan saat load
window.onload = toggleOptions;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
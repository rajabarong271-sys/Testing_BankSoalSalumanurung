<?php
session_start();

// Cek session & role
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] != "admin" && $_SESSION["role"] != "guru")) {
    header("Location: auth/login.php");
    exit;
}

include("../config/db.php");

// =======================
// CREATE (Tambah Mapel)
// =======================
if ($_SESSION["role"] === "admin" && isset($_POST['add_name'])) {
    $name = trim($_POST['add_name']);
    if ($name !== '') {
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO subjects(name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
    }
}

// =======================
// UPDATE (Edit Mapel)
// =======================
if ($_SESSION["role"] === "admin" && isset($_POST['edit_name'], $_POST['edit_id'])) {
    $name = trim($_POST['edit_name']);
    $id   = intval($_POST['edit_id']);
    if ($name !== '') {
        $stmt = $conn->prepare("UPDATE subjects SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
    }
}

// =======================
// DELETE (Hapus Mapel)
// =======================
if ($_SESSION["role"] === "admin" && isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: subjects.php"); // Refresh clean URL
    exit;
}

$subjects = $conn->query("SELECT * FROM subjects ORDER BY name");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Mata Pelajaran</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #334155;
        }
        .navbar-brand {
            font-weight: 700;
            color: #4f46e5 !important;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .table thead th {
            background-color: #f1f5f9;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 600;
            border-top: none;
        }
        .btn-primary {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }
        .btn-warning {
            background-color: #fbbf24;
            border-color: #fbbf24;
            color: #92400e;
        }
        .btn-danger {
            background-color: #ef4444;
            border-color: #ef4444;
        }
        .action-btns .btn {
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            transition: all 0.2s;
        }
        .action-btns .btn:hover {
            transform: translateY(-2px);
        }
        .modal-content {
            border-radius: 16px;
            border: none;
        }
        .badge-role {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom mb-4">
    <div class="container">
        <span class="navbar-brand"><i class="bi bi-journal-bookmark-fill me-2"></i>EduPanel</span>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3 d-none d-md-inline text-muted small">Login sebagai:</span>
            <span class="badge bg-primary-subtle text-primary badge-role fw-semibold uppercase text-capitalize">
                <?= $_SESSION["role"] ?>
            </span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0">Mata Pelajaran</h4>
                    <p class="text-muted small">Kelola data mata pelajaran kurikulum aktif.</p>
                </div>
                <a href="../dashboard/admin.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>

            <!-- Bagian Admin: Tambah Data -->
            <?php if ($_SESSION["role"] === "admin"): ?>
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <form method="post" class="row g-3">
                            <div class="col-md-9">
                                <label class="form-label fw-semibold">Tambah Mapel Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-plus-circle text-muted"></i></span>
                                    <input type="text" class="form-control" name="add_name" placeholder="Contoh: Matematika Peminatan" required>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-primary w-100 py-2 fw-semibold">
                                    <i class="bi bi-send-fill me-2"></i>Tambah
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Table Card -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 py-3" width="80">No</th>
                                <th class="py-3">Nama Mata Pelajaran</th>
                                <?php if ($_SESSION["role"] === "admin"): ?>
                                    <th class="py-3 text-end pe-4">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1; 
                            if ($subjects->num_rows > 0):
                                while ($m = $subjects->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-muted"><?= $i++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                    </td>
                                    <?php if ($_SESSION["role"] === "admin"): ?>
                                        <td class="text-end pe-4 action-btns">
                                            <button class="btn btn-sm btn-warning me-1" 
                                                    onclick="editMapel(<?= $m['id'] ?>, '<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>')">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?= $m['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Belum ada data mata pelajaran.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php if ($_SESSION["role"] === "admin"): ?>
<!-- MODAL EDIT -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content shadow-lg" method="post">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Mapel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label fw-medium text-muted small">Nama Mata Pelajaran</label>
                    <input type="text" name="edit_name" id="edit_name" class="form-control form-control-lg border-2" required>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editMapel(id, name){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    var myModal = new bootstrap.Modal(document.getElementById('editModal'));
    myModal.show();
}

function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus mata pelajaran ini? Data yang dihapus tidak bisa dikembalikan.')) {
        window.location.href = '?delete_id=' + id;
    }
}
</script>
</body>
</html>
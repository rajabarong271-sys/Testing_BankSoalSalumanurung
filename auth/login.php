<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password_input = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // ⚠️ Plaintext password check (Sesuai database saat ini)
        if ($password_input === $user['password']) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            // Logika Pengalihan Berdasarkan Role
            switch ($user["role"]) {
                case "admin":
                    header("Location:../dashboard/admin.php");
                    break;
                case "guru":
                    header("Location:../dashboard/guru.php");
                    break;
                case "siswa":
                    header("Location:../dashboard/siswa.php");
                    break;
                default:
                    header("Location:../index.php");
                    break;
            }
            exit;
        } else {
            $error = "❌ Kata sandi yang Anda masukkan salah!";
        }
    } else {
        $error = "❌ Email tidak ditemukan dalam sistem kami!";
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Bank Soal SMKS Salumanurung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body {
            /* Mengganti background dengan gambar perpustakaan/sekolah yang lebih modern */
            background: #f1f5f9 url('https://images.unsplash.com/photo-1497633762265-9d179a990aa6?q=80&w=1473&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .overlay {
            /* Overlay sedikit lebih gelap untuk meningkatkan kontras card login */
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: var(--glass-bg);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: fadeInUp 0.5s ease-out;
        }

        .login-header {
            background: var(--primary-gradient);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .login-header h4 {
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
            font-size: 1.5rem;
        }

        .login-header p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 5px;
            margin-bottom: 0;
        }

        .card-body { padding: 40px; }

        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border-radius: 12px;
            padding: 14px;
            border: 2px solid #f1f5f9;
            background-color: #f8fafc;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #4f46e5;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 10px;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .alert {
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.85rem;
            border: none;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <h4>SISTEM BANK SOAL</h4>
                <p>SMKS Salumanurung Digital Learning</p>
            </div>

            <div class="card-body">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Email Institusi</label>
                        <input class="form-control" type="email" name="email" placeholder="contoh@smks.sch.id" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">LOGIN</button>
                </form>
            </div>

            <div class="text-center pb-4 text-muted" style="font-size: 0.75rem;">
                &copy; <?= date("Y"); ?> IT Support SMKS Salumanurung
            </div>
        </div>
    </div>
</body>
</html>
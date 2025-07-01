<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php'; // Ensure this path is correct, assuming register.php is in the root directory.

$error = '';

// Ambil daftar kelas yang aktif (menggunakan prepared statement untuk keamanan)
$stmtKelas = $conn->prepare("SELECT Kelas_ID, Nama_Kelas FROM kelas WHERE Status_Kelas = 'Aktif' ORDER BY Nama_Kelas ASC");
$stmtKelas->execute();
$kelasList = $stmtKelas->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $kelas_id = intval($_POST['kelas_id'] ?? 0); // Default to 0 if not set or invalid
    $tanggal_daftar = date('Y-m-d H:i:s'); // Full timestamp for registration date

    // Basic validation
    if (empty($nama) || empty($email) || empty($password_raw) || empty($kelas_id)) {
        $error = "Semua field harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password_raw) < 6) { // Example: minimum 6 characters for password
        $error = "Kata sandi minimal 6 karakter.";
    } else {
        // Check if email already registered (using prepared statement)
        $stmtCheckEmail = $conn->prepare("SELECT User_ID FROM User WHERE Email = ?");
        $stmtCheckEmail->bind_param("s", $email);
        $stmtCheckEmail->execute();
        $cekResult = $stmtCheckEmail->get_result();

        if ($cekResult->num_rows > 0) {
            $error = "Email sudah terdaftar. Silakan gunakan email lain atau login.";
        } else {
            $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);

            // Insert new user (using prepared statement)
            $stmtInsertUser = $conn->prepare("INSERT INTO User (Nama_User, Email, Password, Role, Tanggal_Daftar, Kelas_ID) VALUES (?, ?, ?, ?, ?, ?)");
            $role = 'peserta'; // Default role for registration
            $stmtInsertUser->bind_param("sssssi", $nama, $email, $hashed_password, $role, $tanggal_daftar, $kelas_id);

            if ($stmtInsertUser->execute()) {
                // Redirect to login page with a success message (optional: use session flash message)
                header("Location: login.php?registration=success");
                exit;
            } else {
                $error = "Gagal mendaftar: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #2ecc71, #27ae60); /* Pleasant green gradient for register */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px; /* Padding for small screens */
        }

        .register-card {
            background-color: #ffffff;
            border-radius: 1.25rem; /* More rounded corners */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2); /* More prominent shadow */
            padding: 3rem; /* Increased padding */
            width: 100%;
            max-width: 480px; /* Wider for more inputs */
            animation: fadeInScale 0.5s ease-out; /* Animation on load */
        }

        .register-card h2 {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem; /* Slightly smaller than login for more content */
        }

        .register-card h2 .fas {
            margin-right: 15px;
            color: #2ecc71; /* Green icon color */
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
            display: flex; /* For label icons */
            align-items: center;
        }

        .form-label .fas {
            margin-right: 8px;
            color: #7f8c8d; /* Muted icon color */
        }

        .form-control, .form-select {
            border-radius: 0.75rem; /* More rounded input fields */
            padding: 0.85rem 1.25rem; /* More padding */
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #2ecc71; /* Green border on focus */
            box-shadow: 0 0 0 0.25rem rgba(46, 204, 113, 0.25); /* Green shadow on focus */
        }

        .btn-primary-custom {
            background-color: #2ecc71; /* Consistent primary green */
            border-color: #2ecc71;
            color: #fff;
            border-radius: 0.75rem; /* More rounded button */
            padding: 0.9rem 1.5rem; /* More padding */
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            width: 100%; /* Full width button */
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 1rem; /* Space above button */
        }

        .btn-primary-custom:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-3px); /* Lift effect */
        }

        .alert-danger-custom {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-danger-custom .fas {
            font-size: 1.5rem;
            color: #dc3545; /* Red icon */
        }

        .login-link {
            font-size: 0.95rem;
            color: #6c757d;
            margin-top: 1.5rem;
        }

        .login-link a {
            color: #2ecc71; /* Green for link */
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .login-link a:hover {
            color: #27ae60;
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center">

    <div class="register-card">
        <h2 class="text-center mb-4">
            <i class="fas fa-user-plus"></i> Daftar Akun Baru
        </h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger-custom">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="nama" class="form-label">
                    <i class="fas fa-user"></i> Nama Lengkap
                </label>
                <input type="text" class="form-control" name="nama" id="nama" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Alamat Email
                </label>
                <input type="email" class="form-control" name="email" id="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-key"></i> Kata Sandi
                </label>
                <input type="password" class="form-control" name="password" id="password" required>
                <small class="form-text text-muted">Minimal 6 karakter.</small>
            </div>

            <div class="mb-3">
                <label for="kelas_id" class="form-label">
                    <i class="fas fa-users-class"></i> Pilih Kelas
                </label>
                <select name="kelas_id" id="kelas_id" class="form-select" required>
                    <option value="" disabled <?= !isset($_POST['kelas_id']) || empty($_POST['kelas_id']) ? 'selected' : '' ?>>-- Pilih Kelas --</option>
                    <?php 
                    // Rewind result pointer if needed (in case it was fetched before)
                    if ($kelasList->num_rows > 0) {
                        $kelasList->data_seek(0); // Reset pointer to beginning
                        while ($kelas = $kelasList->fetch_assoc()): 
                            $selected = (isset($_POST['kelas_id']) && $_POST['kelas_id'] == $kelas['Kelas_ID']) ? 'selected' : '';
                    ?>
                        <option value="<?= $kelas['Kelas_ID'] ?>" <?= $selected ?>><?= htmlspecialchars($kelas['Nama_Kelas']) ?></option>
                    <?php 
                        endwhile; 
                    } else {
                        echo "<option value='' disabled>Tidak ada kelas aktif</option>";
                    }
                    ?>
                </select>
                <?php if ($kelasList->num_rows === 0): ?>
                    <small class="form-text text-danger">Tidak ada kelas aktif yang tersedia. Mohon hubungi administrator.</small>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary-custom">
                <i class="fas fa-user-plus me-2"></i> Daftar
            </button>
        </form>

        <div class="mt-3 text-center login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
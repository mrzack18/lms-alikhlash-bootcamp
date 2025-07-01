<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

$id = $_SESSION['user_id'] ?? null;

// Cek apakah user sudah login dan merupakan peserta
if (!$id || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user dan peserta untuk pre-fill form
// Use prepared statements for initial data fetching
$stmtUser = $conn->prepare("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = ?");
$stmtUser->bind_param("i", $id);
$stmtUser->execute();
$qUser = $stmtUser->get_result();
$user = $qUser->fetch_assoc();

$stmtPeserta = $conn->prepare("SELECT Alamat, No_HP, Asal_Sekolah, Status_Lulus FROM peserta WHERE User_ID = ?");
$stmtPeserta->bind_param("i", $id);
$stmtPeserta->execute();
$qPeserta = $stmtPeserta->get_result();
$peserta = $qPeserta->fetch_assoc();


$nama_user = $user['Nama_User'] ?? '';
// Adjust path for default image if no photo is uploaded
$foto_profil_current = !empty($user['Foto_Profile']) ? '../uploads/' . htmlspecialchars($user['Foto_Profile']) : '../default.jpg';

// Pre-fill form from $peserta data
$alamat = $peserta['Alamat'] ?? '';
$no_hp = $peserta['No_HP'] ?? '';
$asal_sekolah = $peserta['Asal_Sekolah'] ?? '';
$status_lulus = $peserta['Status_Lulus'] ?? 'Belum Lulus'; // Default value

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_user_baru = trim($_POST['Nama_User'] ?? '');
    $alamat_baru = trim($_POST['Alamat'] ?? '');
    $no_hp_baru = trim($_POST['No_HP'] ?? '');
    $asal_sekolah_baru = trim($_POST['Asal_Sekolah'] ?? '');
    $status_lulus_baru = trim($_POST['Status_Lulus'] ?? 'Belum Lulus');
    $password_baru = $_POST['Password'] ?? ''; // New password input

    // Start a transaction for multiple updates
    $conn->begin_transaction();
    $update_success = true;

    try {
        // Update tabel user (Nama_User)
        $stmtUpdateUser = $conn->prepare("UPDATE user SET Nama_User = ? WHERE User_ID = ?");
        $stmtUpdateUser->bind_param("si", $nama_user_baru, $id);
        if (!$stmtUpdateUser->execute()) {
            $update_success = false;
        }

        // Update tabel peserta
        $stmtUpdatePeserta = $conn->prepare("
            UPDATE peserta SET
            Alamat = ?,
            No_HP = ?,
            Asal_Sekolah = ?,
            Status_Lulus = ?
            WHERE User_ID = ?
        ");
        $stmtUpdatePeserta->bind_param("ssssi", $alamat_baru, $no_hp_baru, $asal_sekolah_baru, $status_lulus_baru, $id);
        if (!$stmtUpdatePeserta->execute()) {
            $update_success = false;
        }

        // Upload foto (jika ada dan valid)
        if (!empty($_FILES['Foto_Profile']['name']) && $_FILES['Foto_Profile']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            // Generate a unique file name to prevent conflicts
            $fileExtension = pathinfo($_FILES['Foto_Profile']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['Foto_Profile']['tmp_name'], $targetPath)) {
                $stmtUpdatePhoto = $conn->prepare("UPDATE user SET Foto_Profile = ? WHERE User_ID = ?");
                $stmtUpdatePhoto->bind_param("si", $newFileName, $id);
                if (!$stmtUpdatePhoto->execute()) {
                    $update_success = false;
                } else {
                    // Update session for immediate display in navbar if needed
                    $_SESSION['foto_profil'] = $newFileName;
                }
            } else {
                // Handle file upload error
                $update_success = false;
                // Log or display error: "Failed to move uploaded file."
            }
        }

        // Ubah password (jika diisi)
        if (!empty($password_baru)) {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmtUpdatePassword = $conn->prepare("UPDATE user SET Password = ? WHERE User_ID = ?");
            $stmtUpdatePassword->bind_param("si", $hashed_password, $id);
            if (!$stmtUpdatePassword->execute()) {
                $update_success = false;
            }
        }

        if ($update_success) {
            $conn->commit();
            header("Location: profil.php?status=success"); // Redirect on success
            exit;
        } else {
            $conn->rollback();
            header("Location: profil_edit.php?status=error"); // Redirect on error
            exit;
        }
    } catch (Exception $e) {
        $conn->rollback();
        // Log the error $e->getMessage()
        header("Location: profil_edit.php?status=error_exception"); // Redirect on exception
        exit;
    }
}

// Ambil data user lagi untuk memastikan tampilan saat ini jika ada perubahan pada halaman yang sama
$stmtUserAfterPost = $conn->prepare("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = ?");
$stmtUserAfterPost->bind_param("i", $id);
$stmtUserAfterPost->execute();
$qUserAfterPost = $stmtUserAfterPost->get_result();
$userAfterPost = $qUserAfterPost->fetch_assoc();

$nama_user_display = $userAfterPost['Nama_User'] ?? '';
$foto_profil_display = !empty($userAfterPost['Foto_Profile']) ? '../uploads/' . htmlspecialchars($userAfterPost['Foto_Profile']) : '../default.jpg';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Peserta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            /* Lighter background */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: #2c3e50 !important;
            /* Darker, modern primary color (Peserta Theme) */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
        }

        .navbar-brand .fas {
            margin-right: 10px;
            color: #ecf0f1;
            /* Light color for icon */
        }

        .profile-img-container {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.8);
            object-fit: cover;
            transition: transform 0.2s ease-in-out;
        }

        .profile-img-container:hover {
            transform: scale(1.05);
        }

        .dropdown-menu {
            border-radius: 0.75rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.3s ease-out;
            border: none;
        }

        .dropdown-item {
            padding: 0.8rem 1.2rem;
            font-size: 0.95rem;
        }

        .dropdown-item:hover {
            background-color: #e9ecef;
            color: #2c3e50;
        }

        .dropdown-item .fas {
            width: 20px;
            /* Align icons */
            text-align: center;
            margin-right: 10px;
        }

        .main-content {
            flex: 1;
            padding-top: 3rem;
            padding-bottom: 3rem;
        }

        .content-card {
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            max-width: 700px;
            /* Constrain width for forms */
            margin: 0 auto;
        }

        h2 {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2.2rem;
        }

        .current-profile-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #3498db;
            /* Consistent blue border */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border-radius: 0.5rem;
            padding: 0.8rem 1rem;
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .btn-custom-primary {
            background-color: #3498db;
            border-color: #3498db;
            color: #fff;
            border-radius: 0.6rem;
            padding: 0.75rem 1.8rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-custom-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-custom-secondary {
            background-color: transparent;
            border: 1px solid #7f8c8d;
            color: #7f8c8d;
            border-radius: 0.6rem;
            padding: 0.75rem 1.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .btn-custom-secondary:hover {
            background-color: #7f8c8d;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 1.5rem 0;
            text-align: center;
            margin-top: auto;
            /* Pushes footer to bottom */
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        footer p {
            margin-bottom: 0;
            font-size: 0.9rem;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-chalkboard-teacher"></i> Lms Al-Ikhlash Bootcamp Peserta
            </a>
            <div class="d-flex align-items-center gap-3">
                <!-- Dropdown Foto Profil -->
                <div class="dropdown">
                    <a href="#" role="button" id="dropdownProfil" data-bs-toggle="dropdown" aria-expanded="false" class="d-flex align-items-center text-white text-decoration-none">
                        <img src="<?= $foto_profil_display ?>" alt="Foto Profil" class="profile-img-container">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfil">
                        <li>
                            <h6 class="dropdown-header text-muted">Selamat datang, <?= htmlspecialchars($nama_user_display) ?></h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user-circle me-2"></i>Profil Saya</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Konten -->
    <div class="main-content container py-5">
        <div class="content-card">
            <h2 class="mb-4"><i class="fas fa-user-edit me-3"></i>Edit Profil Anda</h2>

            <div class="text-center mb-4">
                <img src="<?= $foto_profil_current ?>" alt="Foto Profil Saat Ini" class="current-profile-photo">
                <p class="text-muted">Foto Profil Saat Ini</p>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="Nama_User" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" name="Nama_User" id="Nama_User" value="<?= htmlspecialchars($nama_user) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="Alamat" class="form-label">Alamat</label>
                    <input type="text" class="form-control" name="Alamat" id="Alamat" value="<?= htmlspecialchars($alamat) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="No_HP" class="form-label">No HP</label>
                    <input type="text" class="form-control" name="No_HP" id="No_HP" value="<?= htmlspecialchars($no_hp) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="Asal_Sekolah" class="form-label">Asal Sekolah</label>
                    <input type="text" class="form-control" name="Asal_Sekolah" id="Asal_Sekolah" value="<?= htmlspecialchars($asal_sekolah) ?>" required>
                </div>

                <div class="mb-4">
                    <label for="Status_Lulus" class="form-label">Status Kelulusan</label>
                    <select name="Status_Lulus" id="Status_Lulus" class="form-select">
                        <option value="Lulus" <?= $status_lulus == 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                        <option value="Belum Lulus" <?= $status_lulus == 'Belum Lulus' ? 'selected' : '' ?>>Belum Lulus</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="Foto_Profile" class="form-label"><i class="fas fa-upload me-2"></i>Upload Foto Profil Baru</label>
                    <input type="file" class="form-control" name="Foto_Profile" id="Foto_Profile" accept="image/*">
                    <small class="form-text text-muted">Ukuran maksimal 2MB. Format: JPG, PNG.</small>
                </div>

                <div class="mb-4">
                    <label for="Password" class="form-label"><i class="fas fa-lock me-2"></i>Password Baru</label>
                    <input type="password" class="form-control" name="Password" id="Password" placeholder="Kosongkan jika tidak ingin diubah">
                    <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah password Anda.</small>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="profil.php" class="btn btn-custom-secondary">
                        <i class="fas fa-times-circle me-2"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-custom-primary">
                        <i class="fas fa-save me-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Lms Al-Ikhlash Bootcamp. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
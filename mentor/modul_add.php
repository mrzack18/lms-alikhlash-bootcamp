<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Cek apakah user sudah login dan merupakan mentor
$userId = $_SESSION['user_id'] ?? null;
if (!$userId || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user untuk nama dan foto profil di navbar
$qUserNavbar = $conn->query("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = $userId");
$dataUserNavbar = $qUserNavbar->fetch_assoc();
$nama_user_navbar = $dataUserNavbar['Nama_User'] ?? 'Mentor';
$foto_profil_navbar = !empty($dataUserNavbar['Foto_Profile']) ? '../uploads/' . htmlspecialchars($dataUserNavbar['Foto_Profile']) : '../default.jpg';

// Proses tambah modul
$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kelas_id = intval($_POST['kelas_id']);
    $tgl = date('Y-m-d'); // Current date for Tgl_Dikirim

    // Basic validation
    if (empty($nama) || empty($deskripsi) || empty($kelas_id) || !isset($_FILES["file"]) || $_FILES["file"]["error"] == UPLOAD_ERR_NO_FILE) {
        $error = "Semua field (Nama Modul, Deskripsi, Lampiran File, Kelas) harus diisi.";
    } else {
        $file_name_uploaded = '';
        if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/';
            $fileExtension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
            // Generate a unique file name
            $newFileName = uniqid('modul_') . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFileName;

            // Basic file type and size validation (adjust as needed)
            $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar']; // Example allowed types
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                $error = "Format file tidak diizinkan. Hanya PDF, DOC, DOCX, PPT, PPTX, ZIP, RAR.";
            } elseif ($_FILES["file"]["size"] > 20 * 1024 * 1024) { // Max 20MB
                $error = "Ukuran file terlalu besar. Maksimal 20MB.";
            } elseif (move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
                $file_name_uploaded = $newFileName;
            } else {
                $error = "Gagal mengupload file. Error: " . $_FILES["file"]["error"];
            }
        } else {
            $error = "Upload file gagal. Error code: " . $_FILES["file"]["error"];
        }

        if (empty($error)) {
            // Insert data into database using prepared statement
            $stmt = $conn->prepare("INSERT INTO modul (Kelas_ID, Nama_Modul, Deskripsi_Modul, Tgl_Dikirim, Url_Modul) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $kelas_id, $nama, $deskripsi, $tgl, $file_name_uploaded);

            if ($stmt->execute()) {
                header("Location: modul_list.php?status=success_add");
                exit;
            } else {
                $error = "Gagal menyimpan modul: " . $conn->error;
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
    <title>Tambah Modul - Mentor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa; /* Lighter background */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: #28a745 !important; /* Bootstrap success green for mentor theme */
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
            color: #ecf0f1; /* Light color for icon */
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
            width: 20px; /* Align icons */
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
            max-width: 700px; /* Constrain width for forms */
            margin: 0 auto;
        }

        h2 {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2.2rem;
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.8rem 1rem;
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #80bdff; /* Bootstrap primary focus */
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .btn-custom-save {
            background-color: #28a745; /* Green for save button */
            border-color: #28a745;
            color: #fff;
            border-radius: 0.6rem;
            padding: 0.75rem 1.8rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-custom-save:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
        }

        .btn-custom-back {
            background-color: transparent;
            border: 1px solid #7f8c8d;
            color: #7f8c8d;
            border-radius: 0.6rem;
            padding: 0.75rem 1.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .btn-custom-back:hover {
            background-color: #7f8c8d;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .alert-custom {
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .alert-danger-custom {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .alert-custom .fas {
            font-size: 2rem;
        }

        footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 1.5rem 0;
            text-align: center;
            margin-top: auto; /* Pushes footer to bottom */
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        footer p {
            margin-bottom: 0;
            font-size: 0.9rem;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-chalkboard-teacher"></i> Dashboard Mentor
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <a href="#" role="button" id="dropdownProfil" data-bs-toggle="dropdown" aria-expanded="false" class="d-flex align-items-center text-white text-decoration-none">
                    <img src="<?= $foto_profil_navbar ?>" alt="Foto Profil" class="profile-img-container">
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfil">
                    <li><h6 class="dropdown-header text-muted">Selamat datang, <?= htmlspecialchars($nama_user_navbar) ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profil_mentor.php"><i class="fas fa-user-circle me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Pengaturan</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="main-content container py-5">
    <div class="content-card">
        <h2 class="mb-4 text-center"><i class="fas fa-plus-circle me-3"></i>Tambah Modul Baru</h2>
        <p class="text-center lead text-muted mb-4">Lengkapi detail di bawah untuk menambahkan modul pembelajaran baru.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger-custom alert-custom">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama" class="form-label"><i class="fas fa-book me-2"></i>Nama Modul</label>
                <input type="text" name="nama" id="nama" class="form-control" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label"><i class="fas fa-info-circle me-2"></i>Deskripsi Modul</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="5" required><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label"><i class="fas fa-file-upload me-2"></i>Lampiran File Modul</label>
                <input type="file" name="file" id="file" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar">
                <small class="form-text text-muted">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, ZIP, RAR. Maksimal 20MB.</small>
            </div>

            <div class="mb-4">
                <label for="kelas_id" class="form-label"><i class="fas fa-users-class me-2"></i>Kelas</label>
                <select name="kelas_id" id="kelas_id" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    // Use prepared statement for fetching classes
                    $qKelas = $conn->query("SELECT Kelas_ID, Nama_Kelas FROM kelas ORDER BY Nama_Kelas ASC");
                    while ($row = $qKelas->fetch_assoc()) {
                        $selected = (isset($_POST['kelas_id']) && $_POST['kelas_id'] == $row['Kelas_ID']) ? 'selected' : '';
                        echo "<option value='{$row['Kelas_ID']}' {$selected}>" . htmlspecialchars($row['Nama_Kelas']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="modul_list.php" class="btn btn-custom-back">
                    <i class="fas fa-times-circle me-2"></i> Batal
                </a>
                <button type="submit" class="btn btn-custom-save">
                    <i class="fas fa-save me-2"></i> Simpan Modul
                </button>
            </div>
        </form>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> Lms Al-Ikhlash Bootcamp. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
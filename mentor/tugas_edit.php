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


// Ambil ID tugas dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data tugas dan nama modul terkait (menggunakan prepared statement)
$stmtTugas = $conn->prepare("SELECT t.Tugas_ID, t.Judul_Tugas, t.Deskripsi_Tugas, t.Batas_Kumpul, t.File_Lampiran, m.Nama_Modul 
                             FROM Tugas t 
                             JOIN Modul m ON t.Modul_ID = m.Modul_ID
                             WHERE t.Tugas_ID = ?");
$stmtTugas->bind_param("i", $id);
$stmtTugas->execute();
$qTugas = $stmtTugas->get_result();

if (!$qTugas || $qTugas->num_rows === 0) {
    // Themed error page if task is not found
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Tugas Tidak Ditemukan</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'><link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'><link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'><style>body {font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; align-items: center; justify-content: center; text-align: center;}.error-container { background-color: #fff; padding: 3rem; border-radius: 1rem; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);}.btn-secondary-custom{background-color: #7f8c8d; border-color: #7f8c8d; color: #fff; border-radius: 0.6rem; padding: 0.75rem 1.8rem; font-weight: 600; transition: background-color 0.2s ease, transform 0.1s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);}.btn-secondary-custom:hover{background-color: #6c7a89; border-color: #6c7a89; transform: translateY(-2px);}</style></head><body><div class='error-container'><i class='fas fa-exclamation-triangle text-warning mb-3' style='font-size: 3rem;'></i><h2 class='mb-3'>Tugas Tidak Ditemukan</h2><p class='lead'>Maaf, tugas yang Anda cari tidak tersedia atau telah dihapus.</p><a href='tugas_list.php' class='btn btn-secondary-custom mt-3'><i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Daftar Tugas</a></div></body></html>";
    exit;
}

$row = $qTugas->fetch_assoc(); // Original data for pre-fill

$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $judul_baru = trim($_POST['judul'] ?? '');
    $deskripsi_baru = trim($_POST['deskripsi'] ?? '');
    $batas_baru = trim($_POST['batas'] ?? ''); // From date input

    // Basic validation
    if (empty($judul_baru) || empty($deskripsi_baru) || empty($batas_baru)) {
        $error = "Judul, Deskripsi, dan Batas Kumpul tidak boleh kosong.";
    } elseif (!DateTime::createFromFormat('Y-m-d', $batas_baru)) { // Validate date format
        $error = "Format tanggal batas kumpul tidak valid.";
    } else {
        // Convert date to YYYY-MM-DD HH:MM:SS for database (end of day)
        $batas_kumpul_db = $batas_baru . ' 23:59:59';

        // Update database using prepared statement
        $stmtUpdate = $conn->prepare("UPDATE Tugas SET Judul_Tugas = ?, Deskripsi_Tugas = ?, Batas_Kumpul = ? WHERE Tugas_ID = ?");
        $stmtUpdate->bind_param("sssi", $judul_baru, $deskripsi_baru, $batas_kumpul_db, $id);
        
        if ($stmtUpdate->execute()) {
            header("Location: tugas_list.php?status=success_edit");
            exit;
        } else {
            $error = "Gagal menyimpan perubahan: " . $conn->error;
        }
    }
    // If there's an error, $row should contain the POSTed values to retain form data
    $row['Judul_Tugas'] = $_POST['judul'] ?? $row['Judul_Tugas'];
    $row['Deskripsi_Tugas'] = $_POST['deskripsi'] ?? $row['Deskripsi_Tugas'];
    $row['Batas_Kumpul'] = $_POST['batas'] ?? $row['Batas_Kumpul']; // Keep the YYYY-MM-DD format for date input
}

// Ensure the Batas_Kumpul is in YYYY-MM-DD format for date input
$batas_kumpul_display = !empty($row['Batas_Kumpul']) ? date('Y-m-d', strtotime($row['Batas_Kumpul'])) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tugas: <?= htmlspecialchars($row['Judul_Tugas']) ?></title>
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
        .current-detail-info {
            background-color: #eaf3fb;
            border-left: 5px solid #3498db;
            padding: 1.2rem;
            border-radius: 0.5rem;
            margin-top: -0.5rem; /* Adjust margin to fit nicely */
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #34495e;
            box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.02);
        }
        .current-detail-info p {
            margin-bottom: 0.5rem;
        }
        .current-detail-info strong {
            color: #2c3e50;
        }
        .current-detail-info .fas {
            margin-right: 8px;
            color: #3498db;
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
        <h2 class="mb-4 text-center"><i class="fas fa-edit me-3"></i>Edit Tugas</h2>
        <p class="text-center lead text-muted mb-4">Perbarui detail tugas ini.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger-custom alert-custom">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="current-detail-info">
            <p><i class="fas fa-book"></i> Modul: <strong><?= htmlspecialchars($row['Nama_Modul']) ?></strong></p>
            <p><i class="fas fa-paperclip"></i> Lampiran Saat Ini: 
                <?php if (!empty($row['File_Lampiran'])): ?>
                    <a href="../uploads/tugas_lampiran/<?= htmlspecialchars($row['File_Lampiran']) ?>" target="_blank">
                        <?= htmlspecialchars($row['File_Lampiran']) ?>
                    </a>
                <?php else: ?>
                    <span class="text-muted">Tidak ada lampiran.</span>
                <?php endif; ?>
            </p>
            <p class="small text-muted mb-0">Lampiran dan Modul tidak bisa diubah dari halaman ini.</p>
        </div>

        <form method="post" enctype="multipart/form-data"> <div class="mb-3">
                <label for="judul" class="form-label"><i class="fas fa-heading me-2"></i>Judul Tugas</label>
                <input type="text" name="judul" id="judul" class="form-control" value="<?= htmlspecialchars($row['Judul_Tugas']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label"><i class="fas fa-info-circle me-2"></i>Deskripsi Tugas</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="5" required><?= htmlspecialchars($row['Deskripsi_Tugas']) ?></textarea>
            </div>

            <div class="mb-4">
                <label for="batas" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Batas Kumpul (Deadline)</label>
                <input type="datetime-local" name="batas" id="batas" class="form-control" value="<?= htmlspecialchars($batas_kumpul_display) ?>" required>
                <small class="form-text text-muted">Tanggal dan waktu terakhir pengumpulan tugas.</small>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="tugas_list.php" class="btn btn-custom-back">
                    <i class="fas fa-times-circle me-2"></i> Batal
                </a>
                <button type="submit" class="btn btn-custom-save">
                    <i class="fas fa-save me-2"></i> Simpan Perubahan
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
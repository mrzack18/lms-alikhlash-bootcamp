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

// Validasi dan ambil ID pengumpulan
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data pengumpulan tugas, beserta detail Tugas, Modul, dan User (menggunakan prepared statement)
$stmt = $conn->prepare("
    SELECT 
        pt.Pengumpulan_ID, pt.Link_Jawaban, pt.File_Jawaban, pt.Waktu_Kumpul, pt.Nilai, pt.Catatan_Mentor, pt.Status_ID,
        u.Nama_User, u.User_ID,
        t.Judul_Tugas, t.Deskripsi_Tugas, t.Batas_Kumpul,
        m.Nama_Modul
    FROM Pengumpulan_Tugas pt
    JOIN User u ON pt.User_ID = u.User_ID
    JOIN Tugas t ON pt.Tugas_ID = t.Tugas_ID
    LEFT JOIN Modul m ON t.Modul_ID = m.Modul_ID
    WHERE pt.Pengumpulan_ID = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    // Themed error page if data not found or invalid ID
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Data Tidak Ditemukan</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'><link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'><link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'><style>body {font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; align-items: center; justify-content: center; text-align: center;}.error-container { background-color: #fff; padding: 3rem; border-radius: 1rem; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);}.btn-secondary-custom{background-color: #7f8c8d; border-color: #7f8c8d; color: #fff; border-radius: 0.6rem; padding: 0.75rem 1.8rem; font-weight: 600; transition: background-color 0.2s ease, transform 0.1s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);}.btn-secondary-custom:hover{background-color: #6c7a89; border-color: #6c7a89; transform: translateY(-2px);}</style></head><body><div class='error-container'><i class='fas fa-exclamation-triangle text-warning mb-3' style='font-size: 3rem;'></i><h2 class='mb-3'>Data Pengumpulan Tidak Ditemukan</h2><p class='lead'>Maaf, data pengumpulan tugas yang Anda cari tidak valid atau telah dihapus.</p><a href='pengumpulan_list.php' class='btn btn-secondary-custom mt-3'><i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Daftar Pengumpulan</a></div></body></html>";
    exit;
}

// Proses penilaian
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nilai = intval($_POST['nilai']);
    $catatan = trim($_POST['catatan'] ?? '');

    // Input validation for nilai
    if ($nilai < 0 || $nilai > 100) {
        $error = "Nilai harus antara 0 dan 100.";
    } else {
        // Update database using prepared statement
        $stmtUpdate = $conn->prepare("UPDATE Pengumpulan_Tugas SET Nilai = ?, Catatan_Mentor = ?, Status_ID = 2 WHERE Pengumpulan_ID = ?");
        $stmtUpdate->bind_param("isi", $nilai, $catatan, $id);
        
        if ($stmtUpdate->execute()) {
            header("Location: pengumpulan_list.php?status=success_grade");
            exit;
        } else {
            $error = "Gagal menyimpan penilaian: " . $conn->error;
        }
    }
    // Retain posted values on error for user convenience
    $data['Nilai'] = $_POST['nilai'] ?? $data['Nilai'];
    $data['Catatan_Mentor'] = $_POST['catatan'] ?? $data['Catatan_Mentor'];
}

// Format submission time
$waktu_kumpul_formatted = (new DateTime($data['Waktu_Kumpul']))->format('d F Y H:i');
$batas_kumpul_formatted = (new DateTime($data['Batas_Kumpul']))->format('d F Y H:i');
$is_overdue_submission = (new DateTime($data['Waktu_Kumpul'])) > (new DateTime($data['Batas_Kumpul']));

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Tugas - Mentor</title>
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
            border-color: #218838;
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

        .submission-details {
            background-color: #eaf3fb; /* Light blue info background */
            border-left: 5px solid #3498db; /* Primary blue border */
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.03);
        }
        .submission-details p {
            margin-bottom: 0.5rem;
            color: #34495e;
            font-size: 0.95rem;
        }
        .submission-details strong {
            color: #2c3e50;
        }
        .submission-details .fas {
            margin-right: 8px;
            color: #3498db;
        }
        .submission-details .submission-link-file {
            display: flex;
            gap: 15px;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }
        .submission-details .submission-link-file a {
            font-weight: 600;
            color: #17a2b8; /* Info blue */
            text-decoration: none;
        }
        .submission-details .submission-link-file a:hover {
            text-decoration: underline;
        }
        .submission-details .submission-link-file .fas {
            color: #17a2b8;
        }
        .text-danger-submission {
            color: #dc3545; /* Red for overdue status */
            font-weight: 600;
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
        <h2 class="mb-4 text-center"><i class="fas fa-star me-3"></i>Form Penilaian Tugas</h2>
        <p class="text-center lead text-muted mb-4">Berikan nilai dan catatan untuk pengumpulan tugas ini.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger-custom alert-custom">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="submission-details">
            <p><i class="fas fa-user"></i> Peserta: <strong><?= htmlspecialchars($data['Nama_User']) ?></strong></p>
            <p><i class="fas fa-tasks"></i> Tugas: <strong><?= htmlspecialchars($data['Judul_Tugas']) ?></strong></p>
            <p><i class="fas fa-book"></i> Modul: <strong><?= htmlspecialchars($data['Nama_Modul'] ?? 'N/A') ?></strong></p>
            <p><i class="fas fa-calendar-check"></i> Waktu Kumpul: 
                <?= $waktu_kumpul_formatted ?>
                <?php if ($is_overdue_submission): ?>
                    <span class="text-danger-submission">(Terlambat)</span>
                <?php endif; ?>
            </p>
            <p><i class="fas fa-hourglass-half"></i> Batas Kumpul: <?= $batas_kumpul_formatted ?></p>
            <p class="mb-2"><i class="fas fa-paperclip"></i> Jawaban:</p>
            <div class="submission-link-file">
                <?php if (!empty($data['Link_Jawaban'])): ?>
                    <a href="<?= htmlspecialchars($data['Link_Jawaban']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Link Jawaban</a>
                <?php else: ?>
                    <span class="text-muted"><i class="fas fa-ban"></i> Link tidak tersedia</span>
                <?php endif; ?>
                
                <?php if (!empty($data['File_Jawaban'])): ?>
                    <a href="../uploads/tugas_submissions/<?= urlencode($data['File_Jawaban']) ?>" target="_blank"><i class="fas fa-download"></i> Download File Jawaban</a>
                <?php else: ?>
                    <span class="text-muted"><i class="fas fa-ban"></i> File tidak tersedia</span>
                <?php endif; ?>
            </div>
        </div>

        <form method="post">
            <div class="mb-3">
                <label for="nilai" class="form-label"><i class="fas fa-percentage me-2"></i>Nilai (0-100)</label>
                <input type="number" name="nilai" id="nilai" class="form-control" min="0" max="100" required value="<?= htmlspecialchars($data['Nilai'] ?? '') ?>">
            </div>

            <div class="mb-4">
                <label for="catatan" class="form-label"><i class="fas fa-comment-dots me-2"></i>Catatan Mentor (opsional)</label>
                <textarea name="catatan" id="catatan" rows="5" class="form-control" placeholder="Berikan masukan atau komentar Anda tentang tugas ini..."><?= htmlspecialchars($data['Catatan_Mentor'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="pengumpulan_list.php" class="btn btn-custom-back">
                    <i class="fas fa-times-circle me-2"></i> Batal
                </a>
                <button type="submit" class="btn btn-custom-save">
                    <i class="fas fa-save me-2"></i> Simpan Penilaian
                </button>
            </div>
        </form>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> EduHub. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
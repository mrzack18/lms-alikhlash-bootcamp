<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Cek apakah user sudah login dan merupakan peserta
$userId = $_SESSION['user_id'] ?? null;
if (!$userId || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user untuk nama dan foto profil di navbar
$qUserNavbar = $conn->query("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = $userId");
$dataUserNavbar = $qUserNavbar->fetch_assoc();
$nama_user_navbar = $dataUserNavbar['Nama_User'] ?? 'Peserta';
$foto_profil_navbar = !empty($dataUserNavbar['Foto_Profile']) ? '../uploads/' . htmlspecialchars($dataUserNavbar['Foto_Profile']) : '../default.jpg';

// Ambil ID tugas dari URL
$tugas_id = intval($_GET['tugas_id'] ?? 0);

// Ambil detail tugas untuk ditampilkan di halaman
$stmtTugasDetail = $conn->prepare("SELECT t.Judul_Tugas, m.Nama_Modul, t.Batas_Kumpul FROM tugas t JOIN modul m ON t.Modul_ID = m.Modul_ID WHERE t.Tugas_ID = ?");
$stmtTugasDetail->bind_param("i", $tugas_id);
$stmtTugasDetail->execute();
$resultTugasDetail = $stmtTugasDetail->get_result();

if ($resultTugasDetail->num_rows === 0) {
    // Handle case where task is not found (themed error)
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Tugas Tidak Ditemukan</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'><link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'><link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'><style>body {font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; align-items: center; justify-content: center; text-align: center;}.error-container { background-color: #fff; padding: 3rem; border-radius: 1rem; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);}.btn-secondary-custom{background-color: #7f8c8d; border-color: #7f8c8d; color: #fff; border-radius: 0.6rem; padding: 0.75rem 1.8rem; font-weight: 600; transition: background-color 0.2s ease, transform 0.1s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);}.btn-secondary-custom:hover{background-color: #6c7a89; border-color: #6c7a89; transform: translateY(-2px);}</style></head><body><div class='error-container'><i class='fas fa-exclamation-triangle text-warning mb-3' style='font-size: 3rem;'></i><h2 class='mb-3'>Tugas Tidak Ditemukan</h2><p class='lead'>Maaf, tugas yang Anda cari tidak tersedia atau telah dihapus.</p><a href='pengumpulan_list.php' class='btn btn-secondary-custom mt-3'><i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Daftar Pengumpulan</a></div></body></html>";
    exit;
}
$tugasDetail = $resultTugasDetail->fetch_assoc();
$judul_tugas = $tugasDetail['Judul_Tugas'];
$nama_modul = $tugasDetail['Nama_Modul'];
$batas_kumpul = new DateTime($tugasDetail['Batas_Kumpul']);
$is_overdue = $batas_kumpul < new DateTime();


$error = ''; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Prevent submission if overdue
    if ($is_overdue) {
        $error = "Tugas ini sudah melewati batas waktu pengumpulan.";
    } else {
        $link = trim($_POST['link'] ?? '');
        $waktu = date('Y-m-d H:i:s');
        $filename = '';

        // Validate if either link or file is provided
        if (empty($link) && empty($_FILES['file']['name'])) {
            $error = "Mohon berikan link jawaban atau upload file jawaban.";
        } else {
            // File upload handling
            if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/tugas_submissions/'; // Dedicated folder for submissions
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
                }

                $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $newFileName = 'tugas_' . $tugas_id . '_user_' . $userId . '_' . uniqid() . '.' . $fileExtension;
                $targetPath = $uploadDir . $newFileName;

                // Basic file type validation
                $allowedTypes = ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
                if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                    $error = "Format file tidak diizinkan. Mohon upload PDF, DOC, DOCX, ZIP, RAR, JPG, JPEG, atau PNG.";
                } elseif ($_FILES['file']['size'] > 5 * 1024 * 1024) { // Max 5MB
                    $error = "Ukuran file terlalu besar. Maksimal 5MB.";
                } elseif (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                    $error = "Gagal mengupload file. Silakan coba lagi.";
                } else {
                    $filename = $newFileName;
                }
            }

            // Only proceed with DB insert if no file upload error
            if (empty($error)) {
                // Check if an entry already exists for this user and task (prevent duplicate submissions)
                $stmtCheck = $conn->prepare("SELECT Pengumpulan_ID FROM Pengumpulan_Tugas WHERE Tugas_ID = ? AND User_ID = ?");
                $stmtCheck->bind_param("ii", $tugas_id, $userId);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();

                if ($resCheck->num_rows > 0) {
                    // Update existing submission
                    $stmtUpdate = $conn->prepare("UPDATE Pengumpulan_Tugas SET Link_Jawaban = ?, File_Jawaban = ?, Waktu_Kumpul = ?, Status_ID = 1 WHERE Tugas_ID = ? AND User_ID = ?");
                    $stmtUpdate->bind_param("ssiii", $link, $filename, $waktu, $tugas_id, $userId);
                    if ($stmtUpdate->execute()) {
                        header("Location: pengumpulan_list.php?status=success_update");
                        exit;
                    } else {
                        $error = "Gagal memperbarui pengumpulan: " . $conn->error;
                    }
                } else {
                    // Insert new submission
                    $stmtInsert = $conn->prepare("
                        INSERT INTO Pengumpulan_Tugas 
                        (Tugas_ID, User_ID, Link_Jawaban, File_Jawaban, Waktu_Kumpul, Status_ID)
                        VALUES (?, ?, ?, ?, ?, 1)
                    ");
                    $stmtInsert->bind_param("iissi", $tugas_id, $userId, $link, $filename, $waktu);
                    if ($stmtInsert->execute()) {
                        header("Location: pengumpulan_list.php?status=success");
                        exit;
                    } else {
                        $error = "Gagal menyimpan pengumpulan: " . $conn->error;
                    }
                }
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
    <title>Kumpulkan Tugas: <?= htmlspecialchars($judul_tugas) ?></title>
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
            background-color: #2c3e50 !important; /* Darker, modern primary color (Peserta Theme) */
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
            max-width: 600px; /* Constrain width for forms */
            margin: 0 auto;
        }

        h2 {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 2.2rem;
        }

        .task-info {
            background-color: #e7f3ff;
            border-left: 5px solid #3498db;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.03);
        }
        .task-info p {
            margin-bottom: 0.5rem;
            color: #34495e;
            font-size: 0.95rem;
        }
        .task-info strong {
            color: #2c3e50;
        }
        .task-info .fas {
            color: #3498db;
            margin-right: 8px;
        }
        .task-info .text-danger {
            font-weight: 600;
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 0.5rem;
            padding: 0.8rem 1rem;
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .btn-custom-submit {
            background-color: #28a745; /* Green for submit */
            border-color: #28a745;
            color: #fff;
            border-radius: 0.6rem;
            padding: 0.85rem 2rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%; /* Full width button */
            margin-top: 1rem;
            font-size: 1.1rem;
        }

        .btn-custom-submit:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
        }

        .btn-custom-submit.disabled {
            background-color: #ced4da !important;
            border-color: #ced4da !important;
            cursor: not-allowed;
            opacity: 0.7;
            transform: none !important;
            box-shadow: none !important;
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
                <i class="fas fa-chalkboard-teacher"></i> Lms Al-Ikhlash Bootcamp Peserta
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <a href="#" role="button" id="dropdownProfil" data-bs-toggle="dropdown" aria-expanded="false" class="d-flex align-items-center text-white text-decoration-none">
                        <img src="<?= $foto_profil_navbar ?>" alt="Foto Profil" class="profile-img-container">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfil">
                        <li><h6 class="dropdown-header text-muted">Selamat datang, <?= htmlspecialchars($nama_user_navbar) ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
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

    <div class="main-content container py-5">
        <div class="content-card">
            <h2 class="mb-4"><i class="fas fa-upload me-3"></i>Kumpulkan Jawaban Tugas</h2>
            <p class="text-center lead text-muted mb-4">Mohon lengkapi formulir di bawah untuk mengirimkan jawaban tugas Anda.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger-custom alert-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="task-info">
                <p><i class="fas fa-tasks"></i> <strong>Judul Tugas:</strong> <?= htmlspecialchars($judul_tugas) ?></p>
                <p><i class="fas fa-book"></i> <strong>Modul:</strong> <?= htmlspecialchars($nama_modul) ?></p>
                <p><i class="fas fa-calendar-alt"></i> <strong>Deadline:</strong> 
                    <span class="<?= $is_overdue ? 'text-danger' : '' ?>">
                        <?= htmlspecialchars($batas_kumpul->format('d F Y H:i')) ?>
                        <?= $is_overdue ? '<br><span class="badge bg-danger">Terlambat</span>' : '' ?>
                    </span>
                </p>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="link" class="form-label"><i class="fas fa-link me-2"></i>Link Jawaban (opsional)</label>
                    <input type="url" name="link" id="link" class="form-control" placeholder="https://contoh.com/jawaban-tugas-anda">
                    <small class="form-text text-muted">Jika jawaban Anda berupa link (misal Google Drive, website).</small>
                </div>
                <div class="mb-4">
                    <label for="file" class="form-label"><i class="fas fa-file-upload me-2"></i>Upload File Jawaban (opsional)</label>
                    <input type="file" name="file" id="file" class="form-control" accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.jpeg,.png">
                    <small class="form-text text-muted">Format: PDF, DOC, DOCX, ZIP, RAR, JPG, JPEG, PNG. Maks. 5MB.</small>
                </div>
                
                <button type="submit" class="btn btn-custom-submit <?= $is_overdue ? 'disabled' : '' ?>"
                        <?= $is_overdue ? 'aria-disabled="true"' : '' ?>>
                    <i class="fas fa-paper-plane me-2"></i> Kumpulkan Tugas
                </button>
            </form>
        </div>
        
        <div class="mt-5 text-center">
            <a href="pengumpulan_list.php" class="btn btn-custom-back">
                <i class="fas fa-arrow-alt-circle-left me-2"></i> Kembali ke Daftar Pengumpulan
            </a>
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
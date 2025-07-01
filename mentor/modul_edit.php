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


// Ambil ID modul dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data modul dari database (menggunakan prepared statement)
$stmtModul = $conn->prepare("SELECT Modul_ID, Nama_Modul, Deskripsi_Modul, Url_Modul, Kelas_ID FROM Modul WHERE Modul_ID = ?");
$stmtModul->bind_param("i", $id);
$stmtModul->execute();
$qModul = $stmtModul->get_result();

if (!$qModul || $qModul->num_rows === 0) {
    // Themed error page if module is not found
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Modul Tidak Ditemukan</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'><link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'><link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'><style>body {font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; align-items: center; justify-content: center; text-align: center;}.error-container { background-color: #fff; padding: 3rem; border-radius: 1rem; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);}.btn-secondary-custom{background-color: #7f8c8d; border-color: #7f8c8d; color: #fff; border-radius: 0.6rem; padding: 0.75rem 1.8rem; font-weight: 600; transition: background-color 0.2s ease, transform 0.1s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);}.btn-secondary-custom:hover{background-color: #6c7a89; border-color: #6c7a89; transform: translateY(-2px);}</style></head><body><div class='error-container'><i class='fas fa-exclamation-triangle text-warning mb-3' style='font-size: 3rem;'></i><h2 class='mb-3'>Modul Tidak Ditemukan</h2><p class='lead'>Maaf, modul yang Anda cari tidak tersedia atau telah dihapus.</p><a href='modul_list.php' class='btn btn-secondary-custom mt-3'><i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Daftar Modul</a></div></body></html>";
    exit;
}

$row = $qModul->fetch_assoc();
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_baru = trim($_POST['nama'] ?? '');
    $deskripsi_baru = trim($_POST['deskripsi'] ?? '');
    $kelas_id_baru = intval($_POST['kelas_id']);
    
    $file_update_sql = '';
    $tgl_update_sql = '';
    $new_file_name = $row['Url_Modul']; // Keep existing file name by default

    // File upload handling
    if (!empty($_FILES['lampiran']['name']) && $_FILES['lampiran']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $fileExtension = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
        // Generate a unique file name
        $newFileName = uniqid('modul_') . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFileName;

        // Basic file type and size validation (adjust as needed)
        $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar']; // Example allowed types
        if (!in_array(strtolower($fileExtension), $allowedTypes)) {
            $error = "Format file tidak diizinkan. Hanya PDF, DOC, DOCX, PPT, PPTX, ZIP, RAR.";
        } elseif ($_FILES['lampiran']['size'] > 20 * 1024 * 1024) { // Max 20MB
            $error = "Ukuran file terlalu besar. Maksimal 20MB.";
        } elseif (move_uploaded_file($_FILES['lampiran']['tmp_name'], $targetPath)) {
            $new_file_name = $newFileName; // Update to new file name
            $file_update_sql = ", Url_Modul = ?";
            $tgl_update_sql = ", Tgl_Dikirim = NOW()";

            // Optional: Delete old file if it exists and is different from the new one
            if (!empty($row['Url_Modul']) && $row['Url_Modul'] !== $new_file_name) {
                $oldFilePath = $uploadDir . $row['Url_Modul'];
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    unlink($oldFilePath); // Delete old file
                }
            }
        } else {
            $error = "Gagal mengupload file. Error: " . $_FILES['lampiran']['error'];
        }
    }

    if (empty($error)) {
        // Build the update query dynamically based on whether a new file was uploaded
        $sql = "UPDATE Modul SET 
                    Nama_Modul = ?, 
                    Deskripsi_Modul = ?, 
                    Kelas_ID = ? 
                    $file_update_sql 
                    $tgl_update_sql 
                WHERE Modul_ID = ?";
        
        $stmtUpdate = $conn->prepare($sql);

        // Determine bind parameters based on file upload
        if (!empty($file_update_sql)) {
            // If new file was uploaded, include new_file_name and current_timestamp in bind
            $stmtUpdate->bind_param("ssisi", $nama_baru, $deskripsi_baru, $kelas_id_baru, $new_file_name, $id);
        } else {
            // No new file, bind only existing data
            $stmtUpdate->bind_param("ssii", $nama_baru, $deskripsi_baru, $kelas_id_baru, $id);
        }

        if ($stmtUpdate->execute()) {
            header("Location: modul_list.php?status=success_edit");
            exit;
        } else {
            $error = "Gagal menyimpan perubahan: " . $conn->error;
        }
    }
}

// Re-fetch modul data after potential POST failure to display current form state
// or if it's a fresh GET request to display original data
$stmtModul->execute();
$qModul = $stmtModul->get_result();
$row = $qModul->fetch_assoc(); // Get the latest data for form pre-fill
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Modul - Mentor</title>
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
        .current-file-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .current-file-info .fas {
            color: #17a2b8; /* Info blue for file icon */
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
        <h2 class="mb-4 text-center"><i class="fas fa-edit me-3"></i>Edit Modul</h2>
        <p class="text-center lead text-muted mb-4">Perbarui informasi dan lampiran untuk modul ini.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger-custom alert-custom">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nama" class="form-label"><i class="fas fa-book me-2"></i>Nama Modul</label>
                <input type="text" name="nama" id="nama" class="form-control" value="<?= htmlspecialchars($row['Nama_Modul']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="deskripsi" class="form-label"><i class="fas fa-info-circle me-2"></i>Deskripsi Modul</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="5" required><?= htmlspecialchars($row['Deskripsi_Modul']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="kelas_id" class="form-label"><i class="fas fa-users-class me-2"></i>Kelas</label>
                <select name="kelas_id" id="kelas_id" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    // Use prepared statement for fetching classes
                    $qKelas = $conn->query("SELECT Kelas_ID, Nama_Kelas FROM kelas ORDER BY Nama_Kelas ASC");
                    while ($kelasRow = $qKelas->fetch_assoc()) {
                        $selected = ($row['Kelas_ID'] == $kelasRow['Kelas_ID']) ? 'selected' : '';
                        echo "<option value='{$kelasRow['Kelas_ID']}' {$selected}>" . htmlspecialchars($kelasRow['Nama_Kelas']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="lampiran" class="form-label"><i class="fas fa-file-upload me-2"></i>Lampiran Modul (opsional)</label>
                <?php if (!empty($row['Url_Modul'])): ?>
                    <div class="current-file-info">
                        <i class="fas fa-paperclip"></i>
                        File saat ini: <a href="../uploads/<?= htmlspecialchars($row['Url_Modul']) ?>" target="_blank">
                                        <?= htmlspecialchars($row['Url_Modul']) ?>
                                      </a>
                    </div>
                <?php else: ?>
                    <p class="small text-muted">Belum ada file modul terlampir.</p>
                <?php endif; ?>
                <input type="file" name="lampiran" id="lampiran" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar">
                <small class="form-text text-muted">Unggah file baru untuk mengganti yang lama. Format: PDF, DOC, DOCX, PPT, PPTX, ZIP, RAR. Maksimal 20MB.</small>
            </div>

            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="modul_list.php" class="btn btn-custom-back">
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
        <p>&copy; <?= date('Y') ?> EduHub. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
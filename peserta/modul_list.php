<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Cek apakah user sudah login dan merupakan peserta
$id = $_SESSION['user_id'] ?? null;
if (!$id || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user untuk nama dan foto profil di navbar
$qUserNavbar = $conn->query("SELECT Nama_User, Foto_Profile, Kelas_ID FROM user WHERE User_ID = $id");
$dataUserNavbar = $qUserNavbar->fetch_assoc();
$nama_user_navbar = $dataUserNavbar['Nama_User'] ?? 'Peserta';
$foto_profil_navbar = !empty($dataUserNavbar['Foto_Profile']) ? '../uploads/' . htmlspecialchars($dataUserNavbar['Foto_Profile']) : '../default.jpg';
$kelas_id = $dataUserNavbar['Kelas_ID'] ?? null;

// Handle case where Kelas_ID is not set for the user
if (!$kelas_id) {
    // Provide a themed error message and exit gracefully
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Kelas Tidak Ditemukan</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'><link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'><link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'><style>body {font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; align-items: center; justify-content: center; text-align: center;}.error-container { background-color: #fff; padding: 3rem; border-radius: 1rem; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);}.btn-secondary-custom{background-color: #7f8c8d; border-color: #7f8c8d; color: #fff; border-radius: 0.6rem; padding: 0.75rem 1.8rem; font-weight: 600; transition: background-color 0.2s ease, transform 0.1s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);}.btn-secondary-custom:hover{background-color: #6c7a89; border-color: #6c7a89; transform: translateY(-2px);}</style></head><body><div class='error-container'><i class='fas fa-exclamation-circle text-danger mb-3' style='font-size: 3rem;'></i><h2 class='mb-3'>Kelas Belum Terdaftar</h2><p class='lead'>Maaf, Kelas Anda belum terdaftar di sistem. Mohon hubungi administrator.</p><a href='dashboard.php' class='btn btn-secondary-custom mt-3'><i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Dashboard</a></div></body></html>";
    exit;
}

// Ambil modul sesuai kelas (menggunakan prepared statement untuk keamanan)
$stmt = $conn->prepare("SELECT * FROM modul WHERE Kelas_ID = ? ORDER BY Modul_ID DESC");
$stmt->bind_param("i", $kelas_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Modul Kelas - Peserta</title>
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
        }

        h2 {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2.2rem;
        }

        .module-list-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive grid for modules */
            gap: 1.5rem;
        }

        .module-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .module-card-body {
            padding: 1.5rem;
        }

        .module-card-title {
            font-weight: 600;
            color: #3498db; /* Blue for module titles */
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }

        .module-card-title .fas {
            margin-right: 12px;
            font-size: 1.5rem;
        }

        .module-card-text {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .module-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            justify-content: flex-end; /* Align buttons to the right */
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

        .btn-custom-link {
            background-color: #ecf0f1; /* Light gray for secondary actions */
            color: #34495e;
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .btn-custom-link:hover {
            background-color: #dde1e5;
            color: #2c3e50;
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
            <i class="fas fa-chalkboard-teacher"></i> EduHub Peserta
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
        <h2 class="mb-4"><i class="fas fa-book-open me-3"></i>Daftar Modul Kelas Anda</h2>
        <p class="text-center lead text-muted mb-4">Pilih modul untuk melihat detail materi dan tugas terkait.</p>

        <div class="module-list-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="card module-card">
                        <div class="module-card-body">
                            <h5 class="module-card-title">
                                <i class="fas fa-file-alt"></i> <?= htmlspecialchars($row['Nama_Modul']) ?>
                            </h5>
                            <p class="module-card-text">
                                <?= htmlspecialchars(mb_strimwidth($row['Deskripsi_Modul'], 0, 100, '...')) ?>
                            </p>
                            <div class="module-actions">
                                <a href="modul_detail.php?id=<?= $row['Modul_ID'] ?>" class="btn btn-custom-link btn-sm">
                                    <i class="fas fa-info-circle me-1"></i> Detail
                                </a>
                                <a href="tugas.php?modul_id=<?= $row['Modul_ID'] ?>" class="btn btn-custom-primary btn-sm">
                                    <i class="fas fa-tasks me-1"></i> Lihat Tugas
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="lead text-muted">Belum ada modul yang tersedia untuk kelas Anda saat ini.</p>
                    <i class="fas fa-box-open" style="font-size: 3rem; color: #ced4da;"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-5 text-center">
            <a href="dashboard.php" class="btn btn-custom-back">
                <i class="fas fa-arrow-alt-circle-left me-2"></i> Kembali ke Dashboard
            </a>
        </div>
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
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Cek apakah user sudah login dan merupakan peserta
$id = $_SESSION['user_id'] ?? null;
if (!$id || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php"); // Redirect to login if not authorized
    exit;
}

// Ambil ID modul dari URL
$modul_id = intval($_GET['id'] ?? 0);

// Ambil data modul dari database menggunakan Prepared Statement untuk keamanan
$stmt = $conn->prepare("SELECT * FROM Modul WHERE Modul_ID = ?");
$stmt->bind_param("i", $modul_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    // Custom styled error page if module is not found
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Modul Tidak Ditemukan</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'><link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css' rel='stylesheet'><link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'><style>body {font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; align-items: center; justify-content: center; text-align: center;}.error-container { background-color: #fff; padding: 3rem; border-radius: 1rem; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);}.btn-secondary-custom{background-color: #7f8c8d; border-color: #7f8c8d; color: #fff; border-radius: 0.6rem; padding: 0.75rem 1.8rem; font-weight: 600; transition: background-color 0.2s ease, transform 0.1s ease; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);}.btn-secondary-custom:hover{background-color: #6c7a89; border-color: #6c7a89; transform: translateY(-2px);}</style></head><body><div class='error-container'><i class='fas fa-exclamation-triangle text-warning mb-3' style='font-size: 3rem;'></i><h2 class='mb-3'>Modul Tidak Ditemukan</h2><p class='lead'>Maaf, modul yang Anda cari tidak tersedia atau telah dihapus.</p><a href='modul_list.php' class='btn btn-secondary-custom mt-3'><i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Daftar Modul</a></div></body></html>";
    exit;
}

$row = $result->fetch_assoc();

// Ambil data user untuk foto profil di navbar (konsisten dengan dashboard)
$userId = $_SESSION['user_id'];
$qUser = $conn->query("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = $userId");
$dataUser = $qUser->fetch_assoc();
$nama_user_navbar = $dataUser['Nama_User'] ?? 'Peserta';
$foto_profil_navbar = !empty($dataUser['Foto_Profile']) ? '../uploads/' . htmlspecialchars($dataUser['Foto_Profile']) : '../default.jpg';

// Set session nama_user jika belum ada (untuk konsistensi dropdown navbar)
if (!isset($_SESSION['nama_user'])) {
    $_SESSION['nama_user'] = $nama_user_navbar;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Modul: <?= htmlspecialchars($row['Nama_Modul']) ?></title>
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
            /* Darker, modern primary color */
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
            /* Slightly larger */
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

        .modul-detail-card {
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            max-width: 900px;
            /* Wider for content */
            margin: 0 auto;
        }

        .modul-title {
            font-weight: 700;
            color: #34495e;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .modul-info p {
            font-size: 1.05rem;
            margin-bottom: 1rem;
            color: #555;
            display: flex;
            align-items: flex-start;
        }

        .modul-info strong {
            color: #34495e;
            min-width: 120px;
            /* Align labels */
            display: inline-block;
        }

        .modul-description {
            background-color: #f8f9fa;
            border-left: 5px solid #3498db;
            padding: 1.5rem 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.03);
        }

        .modul-description p {
            margin-bottom: 0;
            line-height: 1.8;
            color: #666;
        }

        .modul-file-link .fas {
            margin-right: 8px;
            color: #3498db;
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
            background-color: #7f8c8d;
            /* Muted secondary color */
            border-color: #7f8c8d;
            color: #fff;
            border-radius: 0.6rem;
            padding: 0.75rem 1.8rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .btn-custom-secondary:hover {
            background-color: #6c7a89;
            border-color: #6c7a89;
            transform: translateY(-2px);
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
                <!-- Dropdown Foto Profil (consistent with dashboard) -->
                <div class="dropdown">
                    <a href="#" role="button" id="dropdownProfil" data-bs-toggle="dropdown" aria-expanded="false" class="d-flex align-items-center text-white text-decoration-none">
                        <img src="<?= $foto_profil_navbar ?>" alt="Foto Profil" class="profile-img-container">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfil">
                        <li>
                            <h6 class="dropdown-header text-muted">Selamat datang, <?= htmlspecialchars($nama_user_navbar) ?></h6>
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
    <div class="main-content container">
        <div class="modul-detail-card">
            <h2 class="modul-title"><i class="fas fa-book me-3"></i><?= htmlspecialchars($row['Nama_Modul']) ?></h2>

            <div class="modul-info">
                <p><strong><i class="fas fa-file-alt me-2"></i>Deskripsi:</strong></p>
                <div class="modul-description">
                    <p><?= nl2br(htmlspecialchars($row['Deskripsi_Modul'])) ?></p>
                </div>
                <p><strong><i class="fas fa-calendar-alt me-2"></i>Tanggal Dikirim:</strong> <?= htmlspecialchars(date('d F Y', strtotime($row['Tgl_Dikirim']))) ?></p>
                <a href="../uploads/<?= htmlspecialchars($row['Url_Modul']) ?>" target="_blank" class="btn btn-custom-primary btn-sm modul-file-link">
                    Lihat Modul
                </a>
                </p>
            </div>

            <div class="mt-4 text-center">
                <a href="modul_list.php" class="btn btn-custom-secondary">
                    <i class="fas fa-arrow-alt-circle-left me-2"></i> Kembali ke Daftar Modul
                </a>
            </div>
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
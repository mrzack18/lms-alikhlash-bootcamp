<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php'; // Ensure this path is correct

// Cek login role
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'peserta') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Ambil nama user
$qNama = $conn->query("SELECT Nama_User FROM user WHERE User_ID = $userId");
$dataNama = $qNama->fetch_assoc();
$nama = $dataNama['Nama_User'] ?? 'Peserta';

// Ambil foto profil
$q = $conn->query("SELECT Foto_Profile FROM user WHERE User_ID = $userId");
$data = $q->fetch_assoc();
// Adjust path for default image if no photo is uploaded
$foto_profil = !empty($data['Foto_Profile']) ? '../uploads/' . htmlspecialchars($data['Foto_Profile']) : '../default.jpg';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peserta</title>
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
            background-color: #2c3e50 !important; /* Darker, modern primary color */
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
            width: 45px; /* Slightly larger */
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
            padding-top: 4rem;
            padding-bottom: 4rem;
        }

        .welcome-section {
            background: linear-gradient to right, #3498db, #2980b9; /* Blue gradient */
            color: #fff;
            padding: 3rem 0;
            margin-bottom: 3rem;
            border-radius: 1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideInFromTop 0.5s ease-out;
        }

        .welcome-section h2 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .feature-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Responsive grid */
            gap: 2rem;
        }

        .card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); /* More prominent shadow */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02); /* More noticeable lift and slight scale */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-body {
            padding: 2.5rem;
        }

        .card-title {
            color: #34495e; /* Darker title text */
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
        }

        .card-title .fas {
            margin-right: 15px; /* Space between icon and text */
            font-size: 2rem; /* Larger icons */
            color: #3498db; /* Default icon color */
            min-width: 40px; /* Ensure icon area is consistent */
            text-align: center;
        }

        /* Specific icon colors for vibrancy */
        .card-title .fa-book-open { color: #28a745; } /* Green */
        .card-title .fa-award { color: #ffc107; } /* Yellow/Orange */
        .card-title .fa-tasks { color: #6f42c1; } /* Purple */
        .card-title .fa-cloud-upload-alt { color: #dc3545; } /* Red */


        .card-text {
            color: #7f8c8d; /* Muted gray for descriptions */
            margin-bottom: 1.8rem; /* More space below text */
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .btn-primary {
            background-color: #3498db; /* Consistent primary blue */
            border-color: #3498db;
            border-radius: 0.6rem;
            padding: 0.8rem 1.8rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInFromTop {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInFromBottom {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .feature-cards-grid .col {
            animation: slideInFromBottom 0.6s ease-out forwards;
            opacity: 0; /* Start hidden */
        }
        .feature-cards-grid .col:nth-child(1) { animation-delay: 0.1s; }
        .feature-cards-grid .col:nth-child(2) { animation-delay: 0.2s; }
        .feature-cards-grid .col:nth-child(3) { animation-delay: 0.3s; }
        .feature-cards-grid .col:nth-child(4) { animation-delay: 0.4s; }

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
            <a class="navbar-brand" href="#">
                <i class="fas fa-chalkboard-teacher"></i> Lms Al-Ikhlash Bootcamp
            </a>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <a href="#" role="button" id="dropdownProfil" data-bs-toggle="dropdown" aria-expanded="false" class="d-flex align-items-center text-white text-decoration-none">
                        <img src="<?= $foto_profil ?>" alt="Foto Profil" class="profile-img-container">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfil">
                        <li><h6 class="dropdown-header text-muted">Selamat datang, <?= htmlspecialchars($nama) ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user-circle me-2"></i>Profil Saya</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content container">
        <div class="welcome-section text-center">
            <h2 class="mb-3" style="color: #34495e;">Halo, <?= htmlspecialchars($nama) ?></h2>
            <p class="lead card-text">Selamat datang kembali di Dashboard Peserta. Mari lanjutkan perjalanan belajarmu!</p>
        </div>

        <div class="feature-cards-grid">
            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <i class="fas fa-book-open"></i> Lihat Modul
                        </h5>
                        <p class="card-text">Akses semua materi pembelajaran yang terstruktur dan interaktif. Belajar kapan saja, di mana saja.</p>
                        <a href="modul_list.php" class="btn btn-primary mt-auto">Akses Modul</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <i class="fas fa-award"></i> Sertifikat Saya
                        </h5>
                        <p class="card-text">Lihat dan unduh sertifikat kelulusanmu setelah menyelesaikan program.</p>
                        <a href="sertifikat.php" class="btn btn-primary mt-auto">Cek Sertifikat</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <i class="fas fa-tasks"></i> Tugas-tugas
                        </h5>
                        <p class="card-text">Pantau daftar tugas yang harus dikerjakan dan status pengumpulannya.</p>
                        <a href="tugas.php" class="btn btn-primary mt-auto">Lihat Tugas</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <i class="fas fa-cloud-upload-alt"></i> Kirim Tugas
                        </h5>
                        <p class="card-text">Unggah hasil pekerjaanmu dengan mudah dan pastikan tepat waktu.</p>
                        <a href="pengumpulan_list.php" class="btn btn-primary mt-auto">Unggah Tugas</a>
                    </div>
                </div>
            </div>
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
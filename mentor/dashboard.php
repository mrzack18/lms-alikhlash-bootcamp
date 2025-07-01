<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Cek login role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Ambil nama user dan foto profil untuk navbar
$qUser = $conn->query("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = $userId");
$dataUser = $qUser->fetch_assoc();
$nama = $dataUser['Nama_User'] ?? 'Mentor';
// Adjust path for default image if no photo is uploaded
$foto_profil = !empty($dataUser['Foto_Profile']) ? '../uploads/' . htmlspecialchars($dataUser['Foto_Profile']) : '../default.jpg';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mentor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa; /* Light gray background for a softer look */
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
            width: 45px; /* Slightly larger */
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.8); /* Subtle white border */
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
            background: linear-gradient(to right, #28a745, #218838); /* Green gradient */
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
            color: #28a745; /* Default icon color (green for mentor) */
            min-width: 40px; /* Ensure icon area is consistent */
            text-align: center;
        }

        /* Specific icon colors for vibrancy (mentor specific) */
        .card-title .fa-book-open { color: #17a2b8; } /* Info blue */
        .card-title .fa-tasks { color: #6f42c1; } /* Purple */
        .card-title .fa-inbox { color: #ffc107; } /* Warning yellow */
        .card-title .fa-sign-out-alt { color: #dc3545; } /* Danger red */


        .card-text {
            color: #7f8c8d; /* Muted gray for descriptions */
            margin-bottom: 1.8rem; /* More space below text */
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .btn-custom-primary-mentor {
            background-color: #28a745; /* Consistent mentor green */
            border-color: #28a745;
            border-radius: 0.6rem;
            padding: 0.8rem 1.8rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-custom-primary-mentor:hover {
            background-color: #218838;
            border-color: #218838;
            transform: translateY(-2px);
        }
        
        .btn-custom-danger-mentor {
            background-color: #dc3545; /* Danger red for logout */
            border-color: #dc3545;
            border-radius: 0.6rem;
            padding: 0.8rem 1.8rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-custom-danger-mentor:hover {
            background-color: #c82333;
            border-color: #bd2130;
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
                <i class="fas fa-chalkboard-teacher me-2"></i>Dashboard Mentor
            </a>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <a href="#" role="button" id="dropdownProfil" data-bs-toggle="dropdown" aria-expanded="false" class="d-flex align-items-center text-white text-decoration-none">
                        <img src="<?= $foto_profil ?>" alt="Foto Profil" class="profile-img-container">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownProfil">
                        <li><h6 class="dropdown-header text-muted">Selamat datang, <?= htmlspecialchars($nama) ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="profil_mentor.php"><i class="fas fa-user-circle me-2"></i>Profil</a></li>
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
        <div class="welcome-section text-center">
            <h2 class="mb-3">Halo, <?= htmlspecialchars($nama) ?></h2>
            <p class="lead">Selamat datang di Dashboard Mentor. Silakan pilih menu di bawah ini untuk mengelola aktivitas belajar.</p>
        </div>

        <div class="feature-cards-grid">
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-book-open"></i> Kelola Modul
                        </h5>
                        <p class="card-text">Tambahkan, ubah, atau hapus modul pembelajaran untuk peserta Anda.</p>
                        <a href="modul_list.php" class="btn btn-custom-primary-mentor mt-auto">Kelola Modul</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tasks"></i> Kelola Tugas
                        </h5>
                        <p class="card-text">Atur dan buat tugas baru serta lampirannya untuk setiap modul.</p>
                        <a href="tugas_list.php" class="btn btn-custom-primary-mentor mt-auto">Kelola Tugas</a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-inbox"></i> Pengumpulan Tugas
                        </h5>
                        <p class="card-text">Periksa dan nilai tugas yang telah dikumpulkan oleh peserta.</p>
                        <a href="pengumpulan_list.php" class="btn btn-custom-primary-mentor mt-auto">Lihat Pengumpulan</a>
                    </div>
                </div>
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
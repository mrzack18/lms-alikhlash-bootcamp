<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Cek login peserta
$id = $_SESSION['user_id'] ?? null;
if (!$id || $_SESSION['role'] !== 'peserta') {
    header("Location: ../login.php");
    exit;
}

// Ambil data user + peserta + kelas
$q = $conn->query("SELECT u.Nama_User, u.Foto_Profile, u.Kelas_ID,
                          k.Nama_Kelas,
                          p.Alamat, p.No_Hp, p.Asal_Sekolah, p.Status_Lulus
                   FROM user u
                   LEFT JOIN peserta p ON u.User_ID = p.User_ID
                   LEFT JOIN kelas k ON u.Kelas_ID = k.Kelas_ID
                   WHERE u.User_ID = $id");

$data = $q->fetch_assoc();

$nama = $data['Nama_User'] ?? 'Peserta';
$foto_profil = !empty($data['Foto_Profile']) ? '../uploads/' . htmlspecialchars($data['Foto_Profile']) : '../default.jpg';
$alamat = $data['Alamat'] ?? '-';
$no_hp = $data['No_Hp'] ?? '-';
$asal_sekolah = $data['Asal_Sekolah'] ?? '-';
$status_lulus = $data['Status_Lulus'] ?? '-';
$nama_kelas = $data['Nama_Kelas'] ?? '-';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Peserta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: #2c3e50 !important;
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
        }

        .main-content {
            flex: 1;
            padding-top: 3rem;
            padding-bottom: 3rem;
        }

        .profile-container {
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-header {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .profile-photo {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #3498db;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }

        .profile-photo:hover {
            transform: scale(1.05);
        }

        .profile-name {
            font-weight: 700;
            color: #34495e;
            font-size: 2.2rem;
            margin-top: 1rem;
        }

        .profile-title {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .table-profile {
            width: 100%;
            margin-top: 2rem;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .table-profile th, .table-profile td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            border: none;
        }
        .table-profile th {
            background-color: #f0f2f5;
            color: #555;
            font-weight: 600;
            width: 35%;
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }
        .table-profile td {
            background-color: #ffffff;
            color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-top-right-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
        .table-profile tr {
            transition: transform 0.2s ease;
        }
        .table-profile tr:hover {
            transform: translateX(5px);
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
            <div class="d-flex align-items-center">
                <a href="../logout.php" class="btn btn-outline-light rounded-pill px-4">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content container">
        <div class="profile-container">
            <div class="profile-header text-center">
                <img src="<?= $foto_profil ?>" alt="Foto Profil" class="profile-photo mb-3">
                <h3 class="profile-name"><?= htmlspecialchars($nama) ?></h3>
                <p class="profile-title">Profil Peserta</p>
            </div>

            <table class="table-profile">
                <tr>
                    <th><i class="fas fa-map-marker-alt me-2"></i>Alamat</th>
                    <td><?= htmlspecialchars($alamat) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-phone me-2"></i>No HP</th>
                    <td><?= htmlspecialchars($no_hp) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-school me-2"></i>Asal Sekolah</th>
                    <td><?= htmlspecialchars($asal_sekolah) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-user-graduate me-2"></i>Status Lulus</th>
                    <td><?= htmlspecialchars($status_lulus) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-chalkboard me-2"></i>Kelas</th>
                    <td><?= htmlspecialchars($nama_kelas) ?></td>
                </tr>
            </table>

            <div class="mt-5 text-center">
                <a href="profil_edit.php" class="btn btn-custom-primary me-3">
                    <i class="fas fa-edit me-2"></i> Edit Profil
                </a>
                <a href="dashboard.php" class="btn btn-custom-secondary">
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

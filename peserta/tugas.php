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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas - Peserta</title>
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

        .module-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .module-selection-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
            text-decoration: none; /* Remove underline for link-card */
            color: inherit;
        }

        .module-selection-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            color: inherit;
        }

        .module-selection-card-body {
            padding: 1.5rem;
            text-align: center;
        }

        .module-selection-card-title {
            font-weight: 600;
            color: #3498db;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .module-selection-card-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 1rem;
        }

        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table-custom thead th {
            background-color: #eaf3fb; /* Light blue for table header */
            color: #2c3e50;
            font-weight: 600;
            padding: 1rem 1.2rem;
            border-bottom: 2px solid #3498db;
            vertical-align: middle;
        }
        
        .table-custom tbody tr {
            background-color: #ffffff;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03); /* Subtle shadow for rows */
        }

        .table-custom tbody tr:hover {
            background-color: #f0f2f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .table-custom tbody td {
            padding: 1rem 1.2rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6; /* Light border between rows */
        }
        
        /* Remove first row's top border to avoid double border with header */
        .table-custom tbody tr:first-child td {
            border-top: none;
        }

        /* Specific styling for action buttons */
        .table-custom .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 0.4rem;
            font-weight: 500;
        }
        .table-custom .btn-download {
            background-color: #28a745; /* Green for download */
            border-color: #28a745;
            color: #fff;
        }
        .table-custom .btn-download:hover {
            background-color: #218838;
            border-color: #1e7e34;
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
        }
        .alert-custom .fas {
            font-size: 2rem;
        }
        .alert-info-custom {
            background-color: #e7f3ff;
            color: #004085;
            border-color: #b8daff;
        }
        .alert-warning-custom {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
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

        /* Responsive adjustments for table content (same as modul_list_mentor) */
        @media (max-width: 768px) {
            .table-custom thead {
                display: none; /* Hide table headers on small screens */
            }
            .table-custom, .table-custom tbody, .table-custom tr, .table-custom td {
                display: block; /* Make table elements act as blocks */
                width: 100%;
            }
            .table-custom tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.75rem;
                overflow: hidden;
            }
            .table-custom td {
                text-align: right;
                padding-left: 50%; /* Space for pseudo-element labels */
                position: relative;
            }
            .table-custom td::before {
                content: attr(data-label); /* Use data-label for content */
                position: absolute;
                left: 1rem;
                width: calc(50% - 1rem);
                text-align: left;
                font-weight: 600;
                color: #555;
            }
            .table-custom td:nth-of-type(1)::before { content: "Judul:"; }
            .table-custom td:nth-of-type(2)::before { content: "Deskripsi:"; }
            .table-custom td:nth-of-type(3)::before { content: "Deadline:"; }
            .table-custom td:nth-of-type(4)::before { content: "Lampiran:"; }
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
            <h2 class="mb-4"><i class="fas fa-tasks me-3"></i>Daftar Tugas Anda</h2>

            <?php
            // jika belum memilih modul
            if (!isset($_GET['modul_id'])) {
                echo "<p class='lead text-muted text-center mb-4'>Silakan pilih modul untuk melihat tugas yang terkait:</p>";

                // Ambil semua modul (menggunakan prepared statement)
                $qModul = $conn->query("SELECT Modul_ID, Nama_Modul FROM modul ORDER BY Modul_ID DESC");
                
                if ($qModul->num_rows > 0) {
                    echo "<div class='module-selection-grid'>";
                    while ($rowModul = $qModul->fetch_assoc()) {
                        echo "<a href='tugas.php?modul_id=" . $rowModul['Modul_ID'] . "' class='module-selection-card'>
                                <div class='module-selection-card-body'>
                                    <i class='fas fa-folder-open module-selection-card-icon mb-3'></i>
                                    <h5 class='module-selection-card-title'>" . htmlspecialchars($rowModul['Nama_Modul']) . "</h5>
                                    <p class='text-muted small'>Pilih untuk melihat tugas</p>
                                </div>
                              </a>";
                    }
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-warning-custom text-center mt-4'>
                            <i class='fas fa-exclamation-triangle me-2'></i> Tidak ada modul yang tersedia saat ini.
                          </div>";
                }
                ?>
                <div class='mt-5 text-center'>
                    <a href='dashboard.php' class='btn btn-custom-back'>
                        <i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Dashboard
                    </a>
                </div>
                <?php
            } else {
                // jika modul dipilih
                $modul_id = intval($_GET['modul_id']);

                // Ambil nama modul untuk ditampilkan
                $stmtModulName = $conn->prepare("SELECT Nama_Modul FROM modul WHERE Modul_ID = ?");
                $stmtModulName->bind_param("i", $modul_id);
                $stmtModulName->execute();
                $resModulName = $stmtModulName->get_result();
                $modul_name = $resModulName->fetch_assoc()['Nama_Modul'] ?? 'Modul Tidak Ditemukan';

                echo "<h4 class='text-center mb-4 text-primary'><i class='fas fa-book me-2'></i>Tugas untuk Modul: <strong>" . htmlspecialchars($modul_name) . "</strong></h4>";


                // Ambil tugas sesuai modul (menggunakan prepared statement)
                $stmtTugas = $conn->prepare("SELECT * FROM tugas WHERE Modul_ID = ? ORDER BY Batas_Kumpul ASC");
                $stmtTugas->bind_param("i", $modul_id);
                $stmtTugas->execute();
                $qTugas = $stmtTugas->get_result();

                if ($qTugas->num_rows > 0) {
                    echo "
                    <div class='table-responsive'>
                        <table class='table-custom'>
                            <thead>
                                <tr>
                                    <th>Judul Tugas</th>
                                    <th>Deskripsi</th>
                                    <th>Deadline</th>
                                    <th>Lampiran</th>
                                </tr>
                            </thead>
                            <tbody>";
                    while ($rowTugas = $qTugas->fetch_assoc()) {
                        // Format deadline date
                        $deadline = new DateTime($rowTugas['Batas_Kumpul']);
                        $formatted_deadline = $deadline->format('d F Y H:i');

                        echo "<tr>
                                <td>" . htmlspecialchars($rowTugas['Judul_Tugas']) . "</td>
                                <td>" . nl2br(htmlspecialchars(mb_strimwidth($rowTugas['Deskripsi_Tugas'], 0, 100, '...'))) . "</td>
                                <td class='" . (strtotime($rowTugas['Batas_Kumpul']) < time() ? 'text-danger' : '') . "'>" . $formatted_deadline . "</td>
                                <td>";
                                if (!empty($rowTugas['File_Lampiran'])) {
                                    echo "<a href='../uploads/" . rawurlencode($rowTugas['File_Lampiran']) . "' target='_blank' class='btn btn-sm btn-download'>
                                            <i class='fas fa-download me-1'></i> Download
                                        </a>";
                                } else {
                                    echo "<span class='text-muted small'>Tidak ada</span>";
                                }
                                echo "</td>
                            </tr>";
                    }
                    echo "</tbody></table></div>";
                } else {
                    echo "<div class='alert alert-info-custom text-center mt-4'>
                            <i class='fas fa-info-circle me-2'></i> Belum ada tugas yang tersedia untuk modul ini.
                          </div>";
                }
                echo "<div class='mt-4 text-center'>
                        <a href='tugas.php' class='btn btn-custom-back'>
                            <i class='fas fa-arrow-alt-circle-left me-2'></i> Kembali ke Pemilihan Modul
                        </a>
                      </div>";
            }
            ?>
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
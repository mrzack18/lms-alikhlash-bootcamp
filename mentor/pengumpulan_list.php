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

// Ambil data pengumpulan tugas, join hanya dengan user (sesuai permintaan, tanpa tugas/modul detail di sini)
// Menggunakan prepared statement untuk keamanan (meskipun tidak ada input langsung, praktik terbaik)
$res = $conn->query("
    SELECT pt.Pengumpulan_ID, pt.Link_Jawaban, pt.File_Jawaban, pt.Waktu_Kumpul, pt.Nilai, pt.Status_ID, 
           u.Nama_User
    FROM Pengumpulan_Tugas pt
    JOIN User u ON pt.User_ID = u.User_ID
    ORDER BY pt.Waktu_Kumpul DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengumpulan Tugas - Mentor</title>
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
        }

        h2 {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2.2rem;
        }
        
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table-custom thead th {
            background-color: #e6f7ea; /* Lighter green for table header */
            color: #28a745;
            font-weight: 600;
            padding: 1rem 1.2rem;
            border-bottom: 2px solid #28a745;
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
        .table-custom .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .table-custom .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .table-custom .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }
        .table-custom .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
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

        .badge-status-pending {
            background-color: #ffc107; /* Warning yellow */
            color: #343a40;
        }
        .badge-status-graded {
            background-color: #28a745; /* Success green */
            color: #fff;
        }
        .badge-status-overdue { /* Unused here, but kept for consistency with other pages */
            background-color: #dc3545; /* Danger red */
            color: #fff;
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

        /* Responsive adjustments for table content */
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
            /* Specific data-labels for this table */
            .table-custom td:nth-of-type(1)::before { content: "Peserta:"; }
            .table-custom td:nth-of-type(2)::before { content: "Link:"; }
            .table-custom td:nth-of-type(3)::before { content: "File:"; }
            .table-custom td:nth-of-type(4)::before { content: "Status:"; }
            .table-custom td:nth-of-type(5)::before { content: "Nilai:"; }
            .table-custom td:nth-of-type(6)::before { content: "Aksi:"; }
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
        <h2 class="mb-4 text-center"><i class="fas fa-inbox me-3"></i>Daftar Pengumpulan Tugas</h2>
        <p class="text-center lead text-muted mb-4">Periksa dan nilai tugas yang telah dikumpulkan oleh peserta.</p>

        <?php if ($res->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>Link</th>
                            <th>File</th>
                            <th>Status</th>
                            <th>Nilai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $res->fetch_assoc()): ?>
                            <?php
                                // Get status text and class based on Status_ID
                                $status_badge_class = '';
                                $status_text = '';
                                if ($row['Status_ID'] == 1) { // Assuming 1 is Belum Dinilai
                                    $status_badge_class = 'badge-status-pending';
                                    $status_text = 'Belum Dinilai';
                                } elseif ($row['Status_ID'] == 2) { // Assuming 2 is Sudah Dinilai
                                    $status_badge_class = 'badge-status-graded';
                                    $status_text = 'Sudah Dinilai';
                                } else {
                                    $status_badge_class = 'bg-secondary'; // Fallback
                                    $status_text = 'Tidak Diketahui';
                                }
                            ?>
                            <tr>
                                <td data-label="Peserta"><?= htmlspecialchars($row['Nama_User']) ?></td>
                                <td data-label="Link">
                                    <?php if (!empty($row['Link_Jawaban'])): ?>
                                        <a href="<?= htmlspecialchars($row['Link_Jawaban']) ?>" target="_blank" class="text-info"><i class="fas fa-external-link-alt me-1"></i>Lihat</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="File">
                                    <?php if (!empty($row['File_Jawaban'])): ?>
                                        <a href="../uploads/tugas_submissions/<?= urlencode($row['File_Jawaban']) ?>" target="_blank" class="text-success"><i class="fas fa-download me-1"></i>Download</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge <?= $status_badge_class ?>"><?= $status_text ?></span>
                                </td>
                                <td data-label="Nilai">
                                    <?= is_null($row['Nilai']) ? "<span class='text-muted'>-</span>" : "<strong>" . htmlspecialchars($row['Nilai']) . "</strong>" ?>
                                </td>
                                <td data-label="Aksi">
                                    <a href="pengumpulan_nilai.php?id=<?= $row['Pengumpulan_ID'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-star"></i> Nilai</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning-custom text-center py-4">
                <i class="fas fa-exclamation-triangle me-2"></i> Belum ada pengumpulan tugas yang tersedia.
            </div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="dashboard.php" class="btn btn-custom-back">
                <i class="fas fa-arrow-alt-circle-left me-2"></i> Kembali ke Dashboard
            </a>
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
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

// Ambil data tugas dan join dengan nama modul untuk tampilan yang lebih baik
// Using prepared statement if filtering (not strictly needed here without user input for WHERE)
// but good practice for consistency.
$res = $conn->query("
    SELECT t.Tugas_ID, t.Judul_Tugas, t.Deskripsi_Tugas, t.Batas_Kumpul, t.File_Lampiran, m.Nama_Modul 
    FROM tugas t 
    JOIN modul m ON t.Modul_ID = m.Modul_ID
    ORDER BY t.Batas_Kumpul ASC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tugas - Mentor</title>
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

        .btn-custom-add {
            background-color: #28a745; /* Green for add button */
            border-color: #28a745;
            color: #fff;
            border-radius: 0.6rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-custom-add:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
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
        .table-custom .text-muted-link {
            color: #6c757d;
            text-decoration: none;
        }
        .table-custom .text-muted-link:hover {
            text-decoration: underline;
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
        }
        .alert-warning-custom {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        .alert-custom .fas {
            font-size: 2rem;
            color: #ffc107; /* Warning yellow */
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
            .table-custom td:nth-of-type(1)::before { content: "Judul:"; }
            .table-custom td:nth-of-type(2)::before { content: "Modul:"; }
            .table-custom td:nth-of-type(3)::before { content: "Deskripsi:"; }
            .table-custom td:nth-of-type(4)::before { content: "Deadline:"; }
            .table-custom td:nth-of-type(5)::before { content: "Lampiran:"; }
            .table-custom td:nth-of-type(6)::before { content: "Aksi:"; }
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-chalkboard-teacher"></i> Kelola Tugas (Mentor)
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="fas fa-tasks me-3"></i>Daftar Tugas</h2>
            <a href="tugas_add.php" class="btn btn-custom-add">
                <i class="fas fa-plus-circle me-2"></i> Tambah Tugas
            </a>
        </div>
        <p class="text-center lead text-muted mb-4">Kelola semua tugas yang diberikan kepada peserta.</p>

        <?php if ($res->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Judul Tugas</th>
                            <th>Modul</th>
                            <th>Deskripsi</th>
                            <th>Deadline</th>
                            <th>Lampiran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $res->fetch_assoc()): ?>
                            <?php
                                $deadline = new DateTime($row['Batas_Kumpul']);
                                $formatted_deadline = $deadline->format('d F Y H:i');
                                $is_overdue = $deadline < new DateTime();
                            ?>
                            <tr>
                                <td data-label="Judul Tugas"><?= htmlspecialchars($row['Judul_Tugas']) ?></td>
                                <td data-label="Modul"><?= htmlspecialchars($row['Nama_Modul']) ?></td>
                                <td data-label="Deskripsi"><?= htmlspecialchars(mb_strimwidth($row['Deskripsi_Tugas'], 0, 50, '...')) ?></td>
                                <td data-label="Deadline" class="<?= $is_overdue ? 'text-danger' : '' ?>">
                                    <?= $formatted_deadline ?>
                                    <?= $is_overdue ? '<br><span class="badge bg-danger">Terlambat</span>' : '' ?>
                                </td>
                                <td data-label="Lampiran">
                                    <?php if (!empty($row['File_Lampiran'])): ?>
                                        <a href="../uploads/<?= htmlspecialchars($row['File_Lampiran']) ?>" target="_blank" class="text-info">
                                            <i class="fas fa-paperclip me-1"></i> Lampiran
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">- Tidak Ada -</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Aksi">
                                    <a href="tugas_edit.php?id=<?= $row['Tugas_ID'] ?>" class="btn btn-sm btn-primary me-2"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="tugas_delete.php?id=<?= $row['Tugas_ID'] ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus tugas ini?');"><i class="fas fa-trash-alt"></i> Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning-custom text-center py-4">
                <i class="fas fa-exclamation-triangle me-2"></i> Belum ada tugas yang ditambahkan.
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
        <p>&copy; <?= date('Y') ?> EduHub. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include '../db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['role'] != 'peserta') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$q = $conn->query("SELECT * FROM Sertifikat WHERE User_ID = $user_id");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">Sertifikat</a>
        <a href="dashboard.php" class="btn btn-outline-light">Kembali</a>
    </div>
</nav>

<!-- Konten -->
<div class="container py-5">
    <h2 class="text-center mb-4">🎓 Sertifikat Saya</h2>

    <?php if ($q->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php while ($s = $q->fetch_assoc()): ?>
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Kelas ID: <?= htmlspecialchars($s['Kelas_ID']) ?></h5>
                            <p class="card-text">
                                Nilai Akhir: <strong><?= $s['Nilai_Akhir'] ?></strong><br>
                                Terbit: <?= date('d M Y', strtotime($s['Tgl_Daftar_Sertifikat'])) ?>
                            </p>
                            <a href="#" class="btn btn-success disabled">Lihat Sertifikat</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            Belum ada sertifikat yang tersedia.
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

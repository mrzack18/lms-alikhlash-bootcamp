<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

$id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$id || !in_array($role, ['peserta', 'mentor'])) {
    echo "Unauthorized";
    exit;
}

// Ambil data user (nama + foto profil)
$qUser = $conn->query("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = $id");
$user = $qUser->fetch_assoc();
$nama = $user['Nama_User'] ?? 'Pengguna';
$foto_profil = !empty($user['Foto_Profile']) ? $user['Foto_Profile'] : '../default.jpg';

$alamat = $no_hp = $asal_sekolah = $keahlian = $linkedin = null;

if ($role === 'peserta') {
    $qPeserta = $conn->query("SELECT Alamat, No_Hp, Asal_Sekolah FROM peserta WHERE User_ID = $id");
    $peserta = $qPeserta->fetch_assoc();
    $alamat = $peserta['Alamat'] ?? null;
    $no_hp = $peserta['No_Hp'] ?? null;
    $asal_sekolah = $peserta['Asal_Sekolah'] ?? null;
} elseif ($role === 'mentor') {
    $qMentor = $conn->query("SELECT Keahlian, LinkedIn FROM mentor WHERE User_ID = $id");
    $mentor = $qMentor->fetch_assoc();
    $keahlian = $mentor['Keahlian'] ?? null;
    $linkedin = $mentor['LinkedIn'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil <?= ucfirst($role) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #0d6efd;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Dashboard <?= ucfirst($role) ?></a>
        <div class="d-flex">
            <a href="../logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<!-- Konten -->
<div class="container mt-5">
    <div class="text-center mb-4">
        <img src="../uploads/<?= htmlspecialchars($foto_profil) ?>" alt="Foto Profil" class="profile-photo mb-3">
        <h3><?= htmlspecialchars($nama) ?></h3>
        <p class="text-muted">Profil <?= ucfirst($role) ?></p>
    </div>

    <table class="table table-bordered table-striped">
        <?php if ($role === 'peserta'): ?>
            <tr><th>Alamat</th><td><?= htmlspecialchars($alamat ?? '-') ?></td></tr>
            <tr><th>No HP</th><td><?= htmlspecialchars($no_hp ?? '-') ?></td></tr>
            <tr><th>Asal Sekolah</th><td><?= htmlspecialchars($asal_sekolah ?? '-') ?></td></tr>
        <?php elseif ($role === 'mentor'): ?>
            <tr><th>Keahlian</th><td><?= htmlspecialchars($keahlian ?? '-') ?></td></tr>
            <tr><th>LinkedIn</th>
                <td>
                    <?= $linkedin ? "<a href='" . htmlspecialchars($linkedin) . "' target='_blank'>" . htmlspecialchars($linkedin) . "</a>" : '-' ?>
                </td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="mt-4">
        <a href="profil_edit.php" class="btn btn-primary">Edit Profil</a>
        <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

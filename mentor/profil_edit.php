<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

$id = $_SESSION['user_id'] ?? null;

if (!$id || $_SESSION['role'] !== 'mentor') {
    echo "Unauthorized";
    exit;
}

// Ambil data user
$qUser = $conn->query("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = $id");
$user = $qUser->fetch_assoc();
$foto_profil = $user['Foto_Profile'] ?? 'default.png';

// Ambil data mentor
$qMentor = $conn->query("SELECT Keahlian, LinkedIn FROM mentor WHERE User_ID = $id");
$mentor = $qMentor->fetch_assoc();
$keahlian = $mentor['Keahlian'] ?? '';
$linkedin = $mentor['LinkedIn'] ?? '';

// Ambil daftar kelas untuk dropdown
$qKelas = $conn->query("SELECT Nama_Kelas FROM kelas WHERE Status_Kelas = 'Aktif'");
$daftar_kelas = [];
while ($row = $qKelas->fetch_assoc()) {
    $daftar_kelas[] = $row['Nama_Kelas'];
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keahlian_baru = $conn->real_escape_string($_POST['Keahlian'] ?? '');
    $linkedin_baru = $conn->real_escape_string($_POST['LinkedIn'] ?? '');

    $conn->query("
        UPDATE mentor SET 
            Keahlian = '$keahlian_baru',
            LinkedIn = '$linkedin_baru'
        WHERE User_ID = $id
    ");

    header("Location: profil_mentor.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil Mentor</title>
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

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Dashboard Mentor</a>
        <div class="d-flex">
            <a href="../logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="mb-4">Edit Profil Mentor</h2>

    <div class="text-center mb-4">
        <img src="../uploads/<?= htmlspecialchars($foto_profil) ?>" alt="Foto Profil" class="profile-photo mb-2">
        <p class="text-muted">Foto Profil</p>
    </div>

    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label for="Keahlian" class="form-label fw-bold">Keahlian (Kelas yang Diajarkan)</label>
            <select name="Keahlian" id="Keahlian" class="form-select" required>
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($daftar_kelas as $kelas): ?>
                    <option value="<?= htmlspecialchars($kelas) ?>" <?= $kelas == $keahlian ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kelas) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="LinkedIn" class="form-label fw-bold">Link LinkedIn</label>
            <input type="url" class="form-control" name="LinkedIn" id="LinkedIn" value="<?= htmlspecialchars($linkedin) ?>" placeholder="https://linkedin.com/in/nama-kamu" required>
        </div>

        <div class="d-flex justify-content-between">
            <a href="profil_mentor.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

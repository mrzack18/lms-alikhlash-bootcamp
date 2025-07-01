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

// Ambil data user dan peserta
$qUser = $conn->query("SELECT Nama_User, Foto_Profile FROM user WHERE User_ID = $id");
$user = $qUser->fetch_assoc();
$foto_profil = $user['Foto_Profile'] ?? 'default.png';

$qPeserta = $conn->query("SELECT * FROM peserta WHERE User_ID = $id");
$peserta = $qPeserta->fetch_assoc();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alamat = $conn->real_escape_string($_POST['Alamat'] ?? '');
    $no_hp = $conn->real_escape_string($_POST['No_HP'] ?? '');
    $asal_sekolah = $conn->real_escape_string($_POST['Asal_Sekolah'] ?? '');
    $status_lulus = $conn->real_escape_string($_POST['Status_Lulus'] ?? 'Belum Lulus');

    // Update data peserta
    $conn->query("
        UPDATE peserta SET
            Alamat='$alamat',
            No_HP='$no_hp',
            Asal_Sekolah='$asal_sekolah',
            Status_Lulus='$status_lulus'
        WHERE User_ID = $id
    ");

    // Upload foto (jika ada)
    if (!empty($_FILES['Foto_Profile']['name'])) {
        $uploadDir = '../uploads/';
        $fileName = basename($_FILES['Foto_Profile']['name']);
        $targetPath = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($_FILES['Foto_Profile']['tmp_name'], $targetPath)) {
            $filenameToDB = basename($targetPath);
            $conn->query("UPDATE user SET Foto_Profile='$filenameToDB' WHERE User_ID = $id");
        }
    }

    // Ubah password (jika diisi)
    if (!empty($_POST['Password'])) {
        $password_baru = $_POST['Password'];
        $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
        $conn->query("UPDATE user SET Password='$hashed' WHERE User_ID = $id");
    }

    header("Location: profil.php");
    exit;
}

// Pre-fill form
$alamat = $peserta['Alamat'] ?? '';
$no_hp = $peserta['No_HP'] ?? '';
$asal_sekolah = $peserta['Asal_Sekolah'] ?? '';
$status_lulus = $peserta['Status_Lulus'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil</title>
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
            <a class="navbar-brand" href="dashboard.php">Dashboard Peserta</a>
            <div class="d-flex">
                <a href="../logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Edit Profil</h2>

        <div class="text-center mb-4">
            <img src="../uploads/<?= htmlspecialchars($foto_profil) ?>" alt="Foto Profil" class="profile-photo mb-2">
            <p class="text-muted">Foto Profil Saat Ini</p>
        </div>

        <form method="post" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
            <div class="mb-3">
                <label for="Alamat" class="form-label">Alamat</label>
                <input type="text" class="form-control" name="Alamat" id="Alamat" value="<?= htmlspecialchars($alamat) ?>" required>
            </div>

            <div class="mb-3">
                <label for="No_HP" class="form-label">No HP</label>
                <input type="text" class="form-control" name="No_HP" id="No_HP" value="<?= htmlspecialchars($no_hp) ?>" required>
            </div>

            <div class="mb-3">
                <label for="Asal_Sekolah" class="form-label">Asal Sekolah</label>
                <input type="text" class="form-control" name="Asal_Sekolah" id="Asal_Sekolah" value="<?= htmlspecialchars($asal_sekolah) ?>" required>
            </div>

            <div class="mb-3">
                <label for="Status_Lulus" class="form-label">Status Lulus</label>
                <select name="Status_Lulus" id="Status_Lulus" class="form-select">
                    <option value="Lulus" <?= $status_lulus == 'Lulus' ? 'selected' : '' ?>>Lulus</option>
                    <option value="Belum Lulus" <?= $status_lulus == 'Belum Lulus' ? 'selected' : '' ?>>Belum Lulus</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="Foto_Profile" class="form-label">Upload Foto Profil Baru</label>
                <input type="file" class="form-control" name="Foto_Profile" id="Foto_Profile" accept="image/*">
            </div>

            <div class="mb-3">
                <label for="Password" class="form-label">Password Baru</label>
                <input type="password" class="form-control" name="Password" id="Password" placeholder="Kosongkan jika tidak ingin diubah">
            </div>

            <div class="d-flex justify-content-between">
                <a href="profil.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

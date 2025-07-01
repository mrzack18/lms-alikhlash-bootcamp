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

// Validasi parameter
$tugasId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($tugasId <= 0) {
    header("Location: tugas_list.php?error=invalid_id");
    exit;
}

// Ambil data lampiran (jika ada) sebelum dihapus
$q = $conn->prepare("SELECT File_Lampiran FROM tugas WHERE Tugas_ID = ?");
$q->bind_param("i", $tugasId);
$q->execute();
$result = $q->get_result();

if ($result->num_rows === 0) {
    header("Location: tugas_list.php?error=not_found");
    exit;
}

$data = $result->fetch_assoc();

// Hapus file lampiran jika ada
if (!empty($data['File_Lampiran'])) {
    $filePath = '../uploads/' . $data['File_Lampiran'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Hapus tugas dari database
$del = $conn->prepare("DELETE FROM tugas WHERE Tugas_ID = ?");
$del->bind_param("i", $tugasId);

if ($del->execute()) {
    header("Location: tugas_list.php?status=deleted");
    exit;
} else {
    header("Location: tugas_list.php?error=delete_failed");
    exit;
}
?>

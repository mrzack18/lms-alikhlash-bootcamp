<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

if ($_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);

// Cek apakah tugas ada
$q = $conn->query("SELECT File_Lampiran FROM Tugas WHERE Tugas_ID = $id");
$data = $q->fetch_assoc();

if ($data) {
    // Hapus file lampiran jika ada
    if (!empty($data['File_Lampiran'])) {
        $path = '../uploads/' . $data['File_Lampiran'];
        if (file_exists($path)) {
            unlink($path); // hapus file
        }
    }

    // Hapus data dari DB
    $conn->query("DELETE FROM Tugas WHERE Tugas_ID = $id");
}

header("Location: tugas_list.php");
exit;

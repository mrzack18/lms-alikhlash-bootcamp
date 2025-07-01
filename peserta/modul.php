<?php
include '../db.php';
if ($_SESSION['role'] != 'peserta') {
    header("Location: ../login.php");
    exit;
}
$q = $conn->query("SELECT * FROM Modul");
?>
<h2>Modul</h2>
<ul>
<?php
while($m = $q->fetch_assoc()){
  echo "<li>{$m['Nama_Modul']} - 
  <a href='tugas.php?modul_id={$m['Modul_ID']}'>Lihat Tugas</a></li>";
}
?>
</ul>
<a href="dashboard.php">Kembali</a>

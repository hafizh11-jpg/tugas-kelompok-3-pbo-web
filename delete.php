<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    // Menghapus data dari tabel pembelian berdasarkan ID
    $stmt = mysqli_prepare($connection, "DELETE FROM `pembelian` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Kembali ke halaman daftar pembelian
redirect('pembelian/index.php');
?>
<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_pengajuan_post = trim($_POST['kode_pengajuan'] ?? '');
    $nama_barang_post = trim($_POST['nama_barang'] ?? '');
    $kategori_id_post = trim($_POST['kategori_id'] ?? '');
    $jumlah_post = trim($_POST['jumlah'] ?? '');
    $harga_post = trim($_POST['harga'] ?? '');
    $keterangan_post = trim($_POST['keterangan'] ?? '');
    
    if (empty($kode_pengajuan_post) || empty($nama_barang_post) || empty($kategori_id_post) || empty($jumlah_post) || empty($harga_post)) {
        $error = "Kode Pengajuan, Nama Barang, Kategori, Jumlah, dan Harga wajib diisi.";
    }
    
    if (!$error) {
        // Hitung total
        $total_post = $jumlah_post * $harga_post;
        
        $stmt = mysqli_prepare($connection, "INSERT INTO `pembelian` (kode_pengajuan, nama_barang, kategori_id, jumlah, harga, total, keterangan, status, tanggal_pengajuan) VALUES (?, ?, ?, ?, ?, ?, ?, 'diajukan', NOW())");
        mysqli_stmt_bind_param($stmt, "ssiidds", $kode_pengajuan_post, $nama_barang_post, $kategori_id_post, $jumlah_post, $harga_post, $total_post, $keterangan_post);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Pengajuan pembelian berhasil ditambahkan.";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 2000);
            </script>";
        } else {
            $error = "Gagal menyimpan: " . mysqli_error($connection);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>


            <h2>Tambah Pengajuan Pembelian</h2>
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
                <a href="index.php" class="btn btn-secondary">Kembali ke Daftar</a>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Kode Pengajuan*</label>
                        <input type="text" name="kode_pengajuan" class="form-control" required>
                        <small class="text-muted">Format: PB-XXX</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Barang*</label>
                        <input type="text" name="nama_barang" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori*</label>
                        <select name="kategori_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="1">Elektronik</option>
                            <option value="2">Alat Tulis</option>
                            <option value="3">Furniture</option>
                            <option value="4">ATK</option>
                            <option value="5">Lainnya</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jumlah*</label>
                            <input type="number" name="jumlah" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga per Unit*</label>
                            <input type="number" step="any" name="harga" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajukan Pembelian</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </form>
            <?php endif; ?>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
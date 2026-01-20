<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('index.php');

// Mengambil data pengajuan pembelian berdasarkan ID
$stmt = mysqli_prepare($connection, "SELECT id, kode_pengajuan, nama_barang, kategori_id, jumlah, harga, total, keterangan, status FROM `pembelian` WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pembelian = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pembelian) {
    redirect('index.php');
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_pengajuan_post = trim($_POST['kode_pengajuan'] ?? '');
    $nama_barang_post = trim($_POST['nama_barang'] ?? '');
    $kategori_id_post = trim($_POST['kategori_id'] ?? '');
    $jumlah_post = trim($_POST['jumlah'] ?? '');
    $harga_post = trim($_POST['harga'] ?? '');
    $keterangan_post = trim($_POST['keterangan'] ?? '');
    $status_post = trim($_POST['status'] ?? '');

    if (empty($kode_pengajuan_post) || empty($nama_barang_post) || empty($kategori_id_post) || empty($jumlah_post) || empty($harga_post) || empty($status_post)) {
        $error = "Semua field wajib diisi.";
    }

    if (!$error) {
        // Hitung total baru
        $total_post = $jumlah_post * $harga_post;
        
        $stmt = mysqli_prepare($connection, "UPDATE `pembelian` SET `kode_pengajuan` = ?, `nama_barang` = ?, `kategori_id` = ?, `jumlah` = ?, `harga` = ?, `total` = ?, `keterangan` = ?, `status` = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssiiddssi", $kode_pengajuan_post, $nama_barang_post, $kategori_id_post, $jumlah_post, $harga_post, $total_post, $keterangan_post, $status_post, $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Data pengajuan pembelian berhasil diperbarui.";
            mysqli_stmt_close($stmt);
            
            // Ambil ulang data terbaru untuk ditampilkan di form
            $stmt = mysqli_prepare($connection, "SELECT id, kode_pengajuan, nama_barang, kategori_id, jumlah, harga, total, keterangan, status FROM `pembelian` WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $pembelian = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 2000);
            </script>";
        } else {
            $error = "Gagal memperbarui: " . mysqli_error($connection);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

            <h2>Edit Pengajuan Pembelian</h2>
            
            <?php if ($error): ?>
                <?= showAlert($error, 'danger') ?>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <?= showAlert($success, 'success') ?>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Kode Pengajuan*</label>
                    <input type="text" name="kode_pengajuan" class="form-control" value="<?= htmlspecialchars($pembelian['kode_pengajuan']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Barang*</label>
                    <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($pembelian['nama_barang']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori*</label>
                    <select name="kategori_id" class="form-control" required>
                        <option value="1" <?= $pembelian['kategori_id'] == 1 ? 'selected' : '' ?>>Elektronik</option>
                        <option value="2" <?= $pembelian['kategori_id'] == 2 ? 'selected' : '' ?>>Alat Tulis</option>
                        <option value="3" <?= $pembelian['kategori_id'] == 3 ? 'selected' : '' ?>>Furniture</option>
                        <option value="4" <?= $pembelian['kategori_id'] == 4 ? 'selected' : '' ?>>ATK</option>
                        <option value="5" <?= $pembelian['kategori_id'] == 5 ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jumlah*</label>
                        <input type="number" name="jumlah" class="form-control" min="1" value="<?= $pembelian['jumlah'] ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga per Unit*</label>
                        <input type="text" name="harga" class="form-control" value="<?= htmlspecialchars($pembelian['harga']) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control" value="Rp <?= number_format($pembelian['total'], 0, ',', '.') ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($pembelian['keterangan']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status*</label>
                    <select name="status" class="form-control" required>
                        <option value="diajukan" <?= $pembelian['status'] == 'diajukan' ? 'selected' : '' ?>>Diajukan</option>
                        <option value="diproses" <?= $pembelian['status'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="disetujui" <?= $pembelian['status'] == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $pembelian['status'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        <option value="selesai" <?= $pembelian['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Perbarui</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
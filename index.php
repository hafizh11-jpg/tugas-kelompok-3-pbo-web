<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

// Mengambil data dari tabel pembelian
$result = mysqli_query($connection, "SELECT *, DATE_FORMAT(tanggal_pengajuan, '%d/%m/%Y') as tgl_pengajuan FROM `pembelian` ORDER BY id DESC");
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Daftar Pengajuan Pembelian</h2>
                <a href="add.php" class="btn btn-primary">+ Ajukan Pembelian</a>
            </div>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                // Get category name
                                $kategori_names = [
                                    1 => 'Elektronik',
                                    2 => 'Alat Tulis',
                                    3 => 'Furniture',
                                    4 => 'ATK',
                                    5 => 'Lainnya'
                                ];
                                $kategori_name = $kategori_names[$row['kategori_id']] ?? 'Tidak Diketahui';
                                
                                // Status badge color
                                $status_colors = [
                                    'diajukan' => 'warning',
                                    'diproses' => 'info',
                                    'disetujui' => 'success',
                                    'ditolak' => 'danger',
                                    'selesai' => 'primary'
                                ];
                                $status_color = $status_colors[$row['status']] ?? 'secondary';
                            ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['kode_pengajuan']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                    <td><?= htmlspecialchars($kategori_name) ?></td>
                                    <td><?= htmlspecialchars($row['jumlah']) ?></td>
                                    <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                    <td><?= $row['tgl_pengajuan'] ?></td>
                                    <td><span class="badge bg-<?= $status_color ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus pengajuan pembelian ini?')">Hapus</a>
                                        <a href="print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-info">Print</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Belum ada pengajuan pembelian.</div>
            <?php endif; ?>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
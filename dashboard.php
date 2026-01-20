<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

// Hitung statistik
$query_stats = mysqli_query($connection, "
    SELECT 
        COUNT(*) as total_pengajuan,
        SUM(CASE WHEN status = 'diajukan' THEN 1 ELSE 0 END) as diajukan,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
        SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(total) as total_nilai
    FROM pembelian
");

$stats = mysqli_fetch_assoc($query_stats);

// Data untuk chart (pengajuan per bulan)
$query_chart = mysqli_query($connection, "
    SELECT 
        DATE_FORMAT(tanggal_pengajuan, '%Y-%m') as bulan,
        COUNT(*) as jumlah,
        SUM(total) as total
    FROM pembelian 
    WHERE tanggal_pengajuan >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal_pengajuan, '%Y-%m')
    ORDER BY bulan
");

$chart_data = [];
$chart_labels = [];
while($row = mysqli_fetch_assoc($query_chart)) {
    $chart_labels[] = date('M Y', strtotime($row['bulan'] . '-01'));
    $chart_data[] = $row['jumlah'];
}

// Pengajuan terbaru
$query_recent = mysqli_query($connection, "
    SELECT p.*, 
           CASE 
               WHEN p.kategori_id = 1 THEN 'Elektronik'
               WHEN p.kategori_id = 2 THEN 'Alat Tulis'
               WHEN p.kategori_id = 3 THEN 'Furniture'
               WHEN p.kategori_id = 4 THEN 'ATK'
               ELSE 'Lainnya'
           END as kategori_nama
    FROM pembelian p
    ORDER BY tanggal_pengajuan DESC 
    LIMIT 5
");

// Kategori dengan pengajuan terbanyak
$query_top_categories = mysqli_query($connection, "
    SELECT 
        kategori_id,
        COUNT(*) as jumlah,
        CASE 
            WHEN kategori_id = 1 THEN 'Elektronik'
            WHEN kategori_id = 2 THEN 'Alat Tulis'
            WHEN kategori_id = 3 THEN 'Furniture'
            WHEN kategori_id = 4 THEN 'ATK'
            ELSE 'Lainnya'
        END as kategori_nama
    FROM pembelian
    GROUP BY kategori_id
    ORDER BY jumlah DESC
    LIMIT 5
");
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="container-fluid">
    <h2 class="mb-4">Dashboard Pengajuan Pembelian</h2>
    
    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Pengajuan</h5>
                    <h2 class="mb-0"><?= $stats['total_pengajuan'] ?></h2>
                    <small>Total nilai: Rp <?= number_format($stats['total_nilai'] ?? 0, 0, ',', '.') ?></small>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Diajukan</h5>
                    <h2 class="mb-0"><?= $stats['diajukan'] ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Diproses</h5>
                    <h2 class="mb-0"><?= $stats['diproses'] ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Disetujui</h5>
                    <h2 class="mb-0"><?= $stats['disetujui'] ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Ditolak</h5>
                    <h2 class="mb-0"><?= $stats['ditolak'] ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Chart Section -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Trend Pengajuan (6 Bulan Terakhir)</h5>
                </div>
                <div class="card-body">
                    <canvas id="pengajuanChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Top Categories -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 5 Kategori</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($query_top_categories) > 0): ?>
                        <ul class="list-group">
                            <?php while($cat = mysqli_fetch_assoc($query_top_categories)): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= $cat['kategori_nama'] ?>
                                    <span class="badge bg-primary rounded-pill"><?= $cat['jumlah'] ?></span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Belum ada data kategori.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Pengajuan -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pengajuan Terbaru</h5>
            <a href="index.php" class="btn btn-sm btn-primary">Lihat Semua</a>
        </div>
        <div class="card-body">
            <?php if(mysqli_num_rows($query_recent) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($query_recent)): 
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
                                    <td><?= htmlspecialchars($row['kode_pengajuan']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                    <td><?= $row['kategori_nama'] ?></td>
                                    <td><?= $row['jumlah'] ?></td>
                                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal_pengajuan'])) ?></td>
                                    <td><span class="badge bg-<?= $status_color ?>"><?= ucfirst($row['status']) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Belum ada pengajuan pembelian.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart Configuration
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('pengajuanChart').getContext('2d');
    const chartData = <?= json_encode($chart_data) ?>;
    const chartLabels = <?= json_encode($chart_labels) ?>;
    
    const pengajuanChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Jumlah Pengajuan',
                data: chartData,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
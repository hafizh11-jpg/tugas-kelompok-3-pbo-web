<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

// Filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';
$kategori_id = $_GET['kategori_id'] ?? '';

// Build query with filters
$where_conditions = ["tanggal_pengajuan BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'"];
$params = [];

if (!empty($status)) {
    $where_conditions[] = "status = '$status'";
}

if (!empty($kategori_id) && $kategori_id != 'all') {
    $where_conditions[] = "kategori_id = '$kategori_id'";
}

$where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query data
$query = "
    SELECT 
        p.*,
        CASE 
            WHEN p.kategori_id = 1 THEN 'Elektronik'
            WHEN p.kategori_id = 2 THEN 'Alat Tulis'
            WHEN p.kategori_id = 3 THEN 'Furniture'
            WHEN p.kategori_id = 4 THEN 'ATK'
            ELSE 'Lainnya'
        END as kategori_nama,
        DATE_FORMAT(p.tanggal_pengajuan, '%d/%m/%Y %H:%i') as tgl_pengajuan_format
    FROM pembelian p
    $where_clause
    ORDER BY p.tanggal_pengajuan DESC
";

$result = mysqli_query($connection, $query);

// Handle export request
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type == 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan_pembelian_' . date('Ymd_His') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        // CSV header
        $headers = [
            'Kode Pengajuan',
            'Nama Barang',
            'Kategori',
            'Jumlah',
            'Harga per Unit',
            'Total',
            'Keterangan',
            'Status',
            'Tanggal Pengajuan'
        ];
        fputcsv($output, $headers);
        
        // Data rows
        while ($row = mysqli_fetch_assoc($result)) {
            $data = [
                $row['kode_pengajuan'],
                $row['nama_barang'],
                $row['kategori_nama'],
                $row['jumlah'],
                number_format($row['harga'], 0, ',', '.'),
                number_format($row['total'], 0, ',', '.'),
                $row['keterangan'],
                ucfirst($row['status']),
                $row['tgl_pengajuan_format']
            ];
            fputcsv($output, $data);
        }
        
        fclose($output);
        exit();
    } elseif ($export_type == 'excel') {
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="laporan_pembelian_' . date('Ymd_His') . '.xls"');
        
        // Excel header with HTML table
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Laporan Pengajuan Pembelian</title>
            <style>
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #f2f2f2; font-weight: bold; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            </style>
        </head>
        <body>
            <h2>Laporan Pengajuan Pembelian</h2>
            <p>Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</p>';
        
        if (!empty($status)) {
            echo '<p>Status: ' . ucfirst($status) . '</p>';
        }
        if (!empty($kategori_id) && $kategori_id != 'all') {
            $kategori_names = [1=>'Elektronik',2=>'Alat Tulis',3=>'Furniture',4=>'ATK',5=>'Lainnya'];
            echo '<p>Kategori: ' . ($kategori_names[$kategori_id] ?? '') . '</p>';
        }
        
        echo '<table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Pengajuan</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Jumlah</th>
                        <th>Harga per Unit</th>
                        <th>Total</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th>Tanggal Pengajuan</th>
                    </tr>
                </thead>
                <tbody>';
        
        $no = 1;
        mysqli_data_seek($result, 0); // Reset pointer
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>
                    <td>' . $no++ . '</td>
                    <td>' . htmlspecialchars($row['kode_pengajuan']) . '</td>
                    <td>' . htmlspecialchars($row['nama_barang']) . '</td>
                    <td>' . $row['kategori_nama'] . '</td>
                    <td>' . $row['jumlah'] . '</td>
                    <td>' . number_format($row['harga'], 0, ',', '.') . '</td>
                    <td>' . number_format($row['total'], 0, ',', '.') . '</td>
                    <td>' . htmlspecialchars($row['keterangan']) . '</td>
                    <td>' . ucfirst($row['status']) . '</td>
                    <td>' . $row['tgl_pengajuan_format'] . '</td>
                </tr>';
        }
        
        echo '</tbody>
            </table>
        </body>
        </html>';
        exit();
    }
}

// For display page, reset pointer
mysqli_data_seek($result, 0);
$total_rows = mysqli_num_rows($result);

// Calculate summary
$summary_query = "
    SELECT 
        COUNT(*) as total_pengajuan,
        SUM(total) as total_nilai,
        AVG(total) as rata_rata
    FROM pembelian
    $where_clause
";
$summary_result = mysqli_query($connection, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);
?>

<?php include '../views/'.$THEME.'/header.php'; ?>
<?php include '../views/'.$THEME.'/sidebar.php'; ?>
<?php include '../views/'.$THEME.'/topnav.php'; ?>
<?php include '../views/'.$THEME.'/upper_block.php'; ?>

<div class="container-fluid">
    <h2 class="mb-4">Export Laporan Pengajuan Pembelian</h2>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="diajukan" <?= $status == 'diajukan' ? 'selected' : '' ?>>Diajukan</option>
                        <option value="diproses" <?= $status == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="disetujui" <?= $status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        <option value="selesai" <?= $status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select name="kategori_id" class="form-control">
                        <option value="all">Semua Kategori</option>
                        <option value="1" <?= $kategori_id == '1' ? 'selected' : '' ?>>Elektronik</option>
                        <option value="2" <?= $kategori_id == '2' ? 'selected' : '' ?>>Alat Tulis</option>
                        <option value="3" <?= $kategori_id == '3' ? 'selected' : '' ?>>Furniture</option>
                        <option value="4" <?= $kategori_id == '4' ? 'selected' : '' ?>>ATK</option>
                        <option value="5" <?= $kategori_id == '5' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="export.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">Total Data</h5>
                    <h3 class="mb-0"><?= $total_rows ?></h3>
                    <small class="text-muted">pengajuan</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">Total Nilai</h5>
                    <h3 class="mb-0">Rp <?= number_format($summary['total_nilai'] ?? 0, 0, ',', '.') ?></h3>
                    <small class="text-muted">keseluruhan</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">Rata-rata</h5>
                    <h3 class="mb-0">Rp <?= number_format($summary['rata_rata'] ?? 0, 0, ',', '.') ?></h3>
                    <small class="text-muted">per pengajuan</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Buttons -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Export Data</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Export ke Excel</h5>
                            <p class="card-text">Format .xls (Microsoft Excel)</p>
                            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Download Excel
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Export ke CSV</h5>
                            <p class="card-text">Format .csv (Comma Separated Values)</p>
                            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-info">
                                <i class="fas fa-file-csv"></i> Download CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preview Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Preview Data (<?= $total_rows ?> data)</h5>
            <small class="text-muted">Menampilkan maksimal 50 data terbaru</small>
        </div>
        <div class="card-body">
            <?php if ($total_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $display_limit = min($total_rows, 50);
                            for ($i = 0; $i < $display_limit; $i++):
                                $row = mysqli_fetch_assoc($result);
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
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['kode_pengajuan']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                    <td><?= $row['kategori_nama'] ?></td>
                                    <td><?= $row['jumlah'] ?></td>
                                    <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                    <td><span class="badge bg-<?= $status_color ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td><?= $row['tgl_pengajuan_format'] ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_rows > 50): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> Menampilkan 50 dari <?= $total_rows ?> data. Download file untuk melihat semua data.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning">Tidak ada data untuk filter yang dipilih.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../views/'.$THEME.'/lower_block.php'; ?>
<?php include '../views/'.$THEME.'/footer.php'; ?>
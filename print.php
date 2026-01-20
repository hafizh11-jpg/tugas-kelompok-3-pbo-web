<?php
session_start();
require_once '../lib/functions.php';
require_once '../lib/auth.php';

requireAuth();
requireModuleAccess('pembelian');

require_once '../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit();
}

// Mengambil data pengajuan pembelian berdasarkan ID
$query = "
    SELECT 
        p.*,
        DATE_FORMAT(p.tanggal_pengajuan, '%d %M %Y') as tgl_pengajuan_formatted,
        DATE_FORMAT(p.tanggal_pengajuan, '%H:%i') as jam_pengajuan,
        DATE_FORMAT(p.tanggal_update, '%d %M %Y %H:%i') as tgl_update_formatted,
        u.nama as diajukan_oleh,
        u.jabatan as jabatan_pengaju,
        u.departemen as departemen_pengaju,
        CASE 
            WHEN p.kategori_id = 1 THEN 'Elektronik'
            WHEN p.kategori_id = 2 THEN 'Alat Tulis'
            WHEN p.kategori_id = 3 THEN 'Furniture'
            WHEN p.kategori_id = 4 THEN 'ATK'
            ELSE 'Lainnya'
        END as kategori_nama,
        a.nama as approved_by,
        a.jabatan as jabatan_approver,
        pa.approved_at as tgl_approval,
        pa.catatan as catatan_approval
    FROM pembelian p
    LEFT JOIN users u ON p.diajukan_oleh_id = u.id
    LEFT JOIN pembelian_approval pa ON p.id = pa.pembelian_id AND pa.status = 'approved'
    LEFT JOIN users a ON pa.approved_by = a.id
    WHERE p.id = ?
";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pembelian = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pembelian) {
    echo "<script>alert('Data tidak ditemukan!'); window.history.back();</script>";
    exit();
}

// Data perusahaan (bisa disimpan di config)
$company_data = [
    'nama' => 'PT. Contoh Perusahaan',
    'alamat' => 'Jl. Contoh No. 123, Jakarta',
    'telepon' => '(021) 123-4567',
    'email' => 'info@contoh.com',
    'website' => 'www.contoh.com',
    'logo' => '../assets/img/logo.png'
];

// Ambil riwayat approval jika ada
$query_history = "
    SELECT 
        pa.*,
        u.nama as approver_name,
        u.jabatan as approver_jabatan,
        DATE_FORMAT(pa.approved_at, '%d/%m/%Y %H:%i') as tgl_approval_format
    FROM pembelian_approval pa
    LEFT JOIN users u ON pa.approved_by = u.id
    WHERE pa.pembelian_id = ?
    ORDER BY pa.approved_at DESC
";
$stmt_history = mysqli_prepare($connection, $query_history);
mysqli_stmt_bind_param($stmt_history, "i", $id);
mysqli_stmt_execute($stmt_history);
$history_result = mysqli_stmt_get_result($stmt_history);
$approval_history = [];
while($row = mysqli_fetch_assoc($history_result)) {
    $approval_history[] = $row;
}
mysqli_stmt_close($stmt_history);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Detail Pengajuan - <?= $pembelian['kode_pengajuan'] ?></title>
    <style>
        @media print {
            body {
                font-family: 'Times New Roman', Times, serif;
                color: #000;
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            .print-header {
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 15px;
            }
            .print-title {
                font-size: 24px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 5px;
            }
            .print-subtitle {
                font-size: 18px;
                text-align: center;
                margin-bottom: 20px;
            }
            .company-info {
                text-align: center;
                font-size: 12px;
                margin-bottom: 20px;
            }
            .detail-box {
                border: 1px solid #000;
                padding: 15px;
                margin-bottom: 20px;
            }
            .detail-header {
                font-weight: bold;
                border-bottom: 1px solid #000;
                padding-bottom: 5px;
                margin-bottom: 10px;
            }
            .detail-row {
                margin-bottom: 8px;
                display: flex;
            }
            .detail-label {
                width: 200px;
                font-weight: bold;
            }
            .detail-value {
                flex: 1;
            }
            .signature-area {
                margin-top: 50px;
                padding-top: 20px;
                border-top: 1px solid #000;
            }
            .signature-box {
                float: left;
                width: 45%;
                text-align: center;
                margin-bottom: 30px;
            }
            .signature-line {
                width: 200px;
                border-top: 1px solid #000;
                margin: 40px auto 5px;
            }
            .table-print {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .table-print th, .table-print td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            .table-print th {
                background-color: #f0f0f0;
                font-weight: bold;
            }
            .status-badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 3px;
                font-weight: bold;
            }
            .status-diajukan { background-color: #ffc107; color: #000; }
            .status-diproses { background-color: #0dcaf0; color: #fff; }
            .status-disetujui { background-color: #198754; color: #fff; }
            .status-ditolak { background-color: #dc3545; color: #fff; }
            .status-selesai { background-color: #0d6efd; color: #fff; }
        }
        
        /* Screen style */
        @media screen {
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .print-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                border-radius: 10px;
            }
            .print-controls {
                margin-bottom: 20px;
                padding: 15px;
                background: #fff;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .btn-group {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .btn-print {
                background-color: #007bff;
                color: white;
            }
            .btn-back {
                background-color: #6c757d;
                color: white;
            }
            .btn-download {
                background-color: #28a745;
                color: white;
            }
            .btn:hover {
                opacity: 0.9;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <h3>Print Detail Pengajuan</h3>
        <p>Mencetak data pengajuan: <strong><?= $pembelian['kode_pengajuan'] ?></strong></p>
        <div class="btn-group">
            <button class="btn btn-print" onclick="window.print()">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Cetak
            </button>
            <a href="index.php" class="btn btn-back">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Kembali
            </a>
            <button class="btn btn-download" onclick="downloadAsPDF()">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Download PDF
            </button>
        </div>
        <div style="margin-top: 15px; font-size: 12px; color: #666;">
            <p><strong>Tips:</strong> Gunakan landscape printing untuk tampilan yang lebih baik, atau download PDF untuk format yang rapi.</p>
        </div>
    </div>

    <!-- Print Content -->
    <div class="print-container">
        <!-- Header Perusahaan -->
        <div class="print-header">
            <div class="company-info">
                <div style="display: flex; align-items: center; justify-content: center; gap: 20px;">
                    <?php if (file_exists($company_data['logo'])): ?>
                    <div style="width: 80px;">
                        <img src="<?= $company_data['logo'] ?>" alt="Logo" style="max-width: 80px; max-height: 80px;">
                    </div>
                    <?php endif; ?>
                    <div>
                        <h1 style="margin: 0; font-size: 24px;"><?= $company_data['nama'] ?></h1>
                        <p style="margin: 5px 0; font-size: 12px;"><?= $company_data['alamat'] ?></p>
                        <p style="margin: 5px 0; font-size: 12px;">Telp: <?= $company_data['telepon'] ?> | Email: <?= $company_data['email'] ?></p>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <h2 class="print-title">DETAIL PENGAJUAN PEMBELIAN</h2>
                <h3 class="print-subtitle">Nomor: <?= $pembelian['kode_pengajuan'] ?></h3>
            </div>
        </div>

        <!-- Detail Pengajuan -->
        <div class="detail-box">
            <div class="detail-header">INFORMASI PENGAJUAN</div>
            
            <div class="detail-row">
                <div class="detail-label">Kode Pengajuan</div>
                <div class="detail-value"><strong><?= htmlspecialchars($pembelian['kode_pengajuan']) ?></strong></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Tanggal Pengajuan</div>
                <div class="detail-value"><?= $pembelian['tgl_pengajuan_formatted'] ?> (<?= $pembelian['jam_pengajuan'] ?>)</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Nama Barang</div>
                <div class="detail-value"><?= htmlspecialchars($pembelian['nama_barang']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Kategori</div>
                <div class="detail-value"><?= $pembelian['kategori_nama'] ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Diajukan Oleh</div>
                <div class="detail-value">
                    <?= htmlspecialchars($pembelian['diajukan_oleh'] ?? 'N/A') ?><br>
                    <small><?= htmlspecialchars($pembelian['jabatan_pengaju'] ?? '') ?> - <?= htmlspecialchars($pembelian['departemen_pengaju'] ?? '') ?></small>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="status-badge status-<?= $pembelian['status'] ?>">
                        <?= strtoupper($pembelian['status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Detail Pembelian -->
        <div class="detail-box">
            <div class="detail-header">RINCIAN PEMBELIAN</div>
            
            <table class="table-print">
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                        <th>Harga per Unit</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($pembelian['nama_barang']) ?></td>
                        <td><?= number_format($pembelian['jumlah'], 0, ',', '.') ?> unit</td>
                        <td>Rp <?= number_format($pembelian['harga'], 0, ',', '.') ?></td>
                        <td><strong>Rp <?= number_format($pembelian['total'], 0, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>TOTAL</strong></td>
                        <td><strong>Rp <?= number_format($pembelian['total'], 0, ',', '.') ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 15px;">
                <div class="detail-label" style="font-weight: bold;">Keterangan:</div>
                <div style="padding: 10px; border: 1px dashed #666; border-radius: 5px; min-height: 60px;">
                    <?= nl2br(htmlspecialchars($pembelian['keterangan'] ?? '-')) ?>
                </div>
            </div>
        </div>

        <!-- Riwayat Approval -->
        <?php if (!empty($approval_history)): ?>
        <div class="detail-box">
            <div class="detail-header">RIWAYAT APPROVAL</div>
            
            <table class="table-print">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Status</th>
                        <th>Disetujui Oleh</th>
                        <th>Jabatan</th>
                        <th>Tanggal & Waktu</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($approval_history as $index => $approval): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <span class="status-badge status-<?= $approval['status'] ?>">
                                <?= strtoupper($approval['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($approval['approver_name']) ?></td>
                        <td><?= htmlspecialchars($approval['approver_jabatan']) ?></td>
                        <td><?= $approval['tgl_approval_format'] ?></td>
                        <td><?= nl2br(htmlspecialchars($approval['catatan'] ?? '-')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Tanda Tangan -->
        <div class="signature-area">
            <div style="clear: both;"></div>
            
            <div class="signature-box">
                <p><strong>Yang Mengajukan,</strong></p>
                <div class="signature-line"></div>
                <p><?= htmlspecialchars($pembelian['diajukan_oleh'] ?? '') ?></p>
                <p><?= htmlspecialchars($pembelian['jabatan_pengaju'] ?? '') ?></p>
            </div>
            
            <div class="signature-box" style="float: right;">
                <p><strong>Menyetujui,</strong></p>
                <div class="signature-line"></div>
                <p><?= htmlspecialchars($pembelian['approved_by'] ?? '') ?></p>
                <p><?= htmlspecialchars($pembelian['jabatan_approver'] ?? '') ?></p>
                <p><?= $pembelian['tgl_approval'] ? date('d/m/Y', strtotime($pembelian['tgl_approval'])) : '' ?></p>
            </div>
            
            <div style="clear: both;"></div>
        </div>

        <!-- Footer -->
        <div style="margin-top: 50px; font-size: 10px; text-align: center; color: #666;">
            <p>Dokumen ini dicetak secara elektronis dari Sistem Pengajuan Pembelian</p>
            <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?> oleh <?= $_SESSION['user_name'] ?? 'System' ?></p>
        </div>
    </div>

    <script>
        function downloadAsPDF() {
            alert('Fitur download PDF membutuhkan konfigurasi server tambahan.\nUntuk saat ini, gunakan fitur Print > Save as PDF pada browser Anda.');
            // Untuk implementasi nyata, bisa menggunakan library seperti jsPDF atau server-side PDF generation
        }

        // Auto-print jika parameter print=1
        <?php if (isset($_GET['print']) && $_GET['print'] == '1'): ?>
        window.onload = function() {
            window.print();
        }
        <?php endif; ?>

        // Keyboard shortcut
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.history.back();
            }
        });
    </script>

    <!-- Untuk PDF generation, bisa ditambahkan library jsPDF -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script> -->
</body>
</html>
<?php
session_start();
include "../config.php";
requireSiswa();

$siswa_id = (int) $_SESSION['user']['id_user'];
$active_page = 'log';
$page_title  = 'Log Aktivitas';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

// Build query
$where = "WHERE id_users = $siswa_id";
if ($search) {
    $where .= " AND aktivitas LIKE '%$search%'";
}
if ($filter_date) {
    $where .= " AND DATE(waktu) = '$filter_date'";
}

// Get total records
$count_query = "SELECT COUNT(*) as total FROM log_aktivitas $where";
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Get log data with pagination
$query_log = "SELECT * FROM log_aktivitas $where ORDER BY waktu DESC LIMIT $limit OFFSET $offset";
$result_log = mysqli_query($conn, $query_log);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'delete' && isset($_POST['id'])) {
        $log_id = (int)$_POST['id'];
        
        // Verify ownership
        $verify_query = "SELECT id_users FROM log_aktivitas WHERE id_log = $log_id";
        $verify_result = mysqli_query($conn, $verify_query);
        
        if (mysqli_num_rows($verify_result) > 0) {
            $log_data = mysqli_fetch_assoc($verify_result);
            
            if ($log_data['id_users'] == $siswa_id) {
                $delete_query = "DELETE FROM log_aktivitas WHERE id_log = $log_id";
                if (mysqli_query($conn, $delete_query)) {
                    echo json_encode(['success' => true, 'message' => 'Log berhasil dihapus']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus log']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Log tidak ditemukan']);
        }
    } elseif ($_POST['action'] == 'delete_all') {
        $delete_all_query = "DELETE FROM log_aktivitas WHERE id_users = $siswa_id";
        if (mysqli_query($conn, $delete_all_query)) {
            echo json_encode(['success' => true, 'message' => 'Semua log berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus log']);
        }
    }
    exit;
}

// Handle export
// if (isset($_GET['export']) && $_GET['export'] == 'csv') {
//     $query_export = "SELECT * FROM log_aktivitas $where ORDER BY waktu DESC";
//     $result_export = mysqli_query($conn, $query_export);
    
//     header('Content-Type: text/csv; charset=utf-8');
//     header('Content-Disposition: attachment; filename=log_aktivitas_' . date('Y-m-d') . '.csv');
    
//     $output = fopen('php://output', 'w');
//     fputcsv($output, array('No', 'Aktivitas', 'Waktu'), ',');
    
//     $no = 1;
//     while ($row = mysqli_fetch_assoc($result_export)) {
//         fputcsv($output, array(
//             $no++,
//             $row['aktivitas'],
//             date('d-m-Y H:i', strtotime($row['waktu']))
//         ), ',');
//     }
//     fclose($output);
//     exit;
// }

include '_header_siswa.php';
?>

<style>
    .filter-container {
        background: rgba(15, 23, 42, 0.5);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border: 1px solid rgba(147, 197, 253, 0.1);
    }

    .filter-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto auto;
        gap: 10px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-size: 0.85rem;
        color: #94a3b8;
        font-weight: 500;
    }

    .filter-group input,
    .filter-group select {
        padding: 8px 12px;
        border: 1px solid rgba(147, 197, 253, 0.2);
        border-radius: 6px;
        background: rgba(15, 23, 42, 0.8);
        color: #e2e8f0;
        font-size: 0.9rem;
    }

    .filter-group input::placeholder {
        color: #64748b;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-sm {
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-filter {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .btn-filter:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: translateY(-2px);
    }

    .btn-reset {
        background: rgba(107, 114, 128, 0.3);
        color: #cbd5e1;
    }

    .btn-reset:hover {
        background: rgba(107, 114, 128, 0.5);
    }

    .btn-export {
        background: rgba(34, 197, 94, 0.3);
        color: #86efac;
        border: 1px solid rgba(34, 197, 94, 0.5);
    }

    .btn-export:hover {
        background: rgba(34, 197, 94, 0.5);
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(147, 197, 253, 0.1);
        border-radius: 10px;
        padding: 15px;
        text-align: center;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        color: #60a5fa;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #94a3b8;
        margin-top: 5px;
    }

    .activity-item {
        padding: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        transition: background 0.3s;
    }

    .activity-item:hover {
        background: rgba(59, 130, 246, 0.05);
    }

    .activity-content {
        flex: 1;
        display: flex;
        gap: 15px;
    }

    .activity-icon {
        background: rgba(147, 197, 253, 0.1);
        color: #93c5fd;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .activity-text {
        flex: 1;
    }

    .activity-text p {
        margin: 0 0 8px 0;
        font-size: 0.9rem;
        color: #e2e8f0;
    }

    .activity-time {
        font-size: 0.75rem;
        color: #64748b;
    }

    .activity-actions {
        display: flex;
        gap: 5px;
        flex-shrink: 0;
    }

    .btn-delete {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border: 1px solid rgba(239, 68, 68, 0.3);
        padding: 6px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.3s;
    }

    .btn-delete:hover {
        background: rgba(239, 68, 68, 0.4);
        transform: translateY(-2px);
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid rgba(147, 197, 253, 0.2);
        border-radius: 6px;
        color: #93c5fd;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s;
    }

    .pagination a:hover {
        background: rgba(147, 197, 253, 0.1);
        border-color: rgba(147, 197, 253, 0.4);
    }

    .pagination .active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 10px;
        display: block;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr;
        }

        .activity-content {
            flex-direction: column;
        }

        .activity-item {
            flex-direction: column;
        }
    }
</style>

<div class="page-header" style="margin-bottom:20px;">
    <h2><i class="fas fa-history"></i> Riwayat Aktivitas</h2>
    <p style="color: #94a3b8; font-size: 0.9rem;">Menampilkan aktivitas akun Anda</p>
</div>

<!-- Statistics -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-number"><?= $total_records ?></div>
        <div class="stat-label">Total Aktivitas</div>
    </div>
    <!-- <div class="stat-card">
        <div class="stat-number"><?= ceil($total_records / $limit) ?></div>
        <div class="stat-label">Halaman</div>
    </div> -->
</div>

<!-- Filter Section -->
<!-- <div class="filter-container">
    <form method="GET" style="margin: 0;">
        <div class="filter-row">
            <div class="filter-group">
                <label for="search">Cari Aktivitas</label>
                <input type="text" id="search" name="search" placeholder="Ketik aktivitas..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
                <label for="filter_date">Tanggal</label>
                <input type="date" id="filter_date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-sm btn-filter">
                    <i class="fas fa-search"></i> Cari
                </button>
                <a href="siswa-log.php" class="btn-sm btn-reset" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-redo"></i> Reset
                </a>
                <a href="?export=csv<?= $search ? '&search=' . urlencode($search) : '' ?><?= $filter_date ? '&filter_date=' . urlencode($filter_date) : '' ?>" class="btn-sm btn-export" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-download"></i> CSV
                </a>
                <?php if ($total_records > 0): ?>
                    <button type="button" class="btn-sm btn-delete" onclick="deleteAll()" style="border: 1px solid rgba(239, 68, 68, 0.3);">
                        <i class="fas fa-trash"></i> Hapus Semua
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div> -->

<!-- Log List -->
<div class="card">
    <?php if (mysqli_num_rows($result_log) > 0): ?>
        <div style="display: flex; flex-direction: column;">
            <?php $no = $offset + 1; ?>
            <?php while ($log = mysqli_fetch_assoc($result_log)): ?>
                <div class="activity-item" data-log-id="<?= $log['id_log'] ?>">
                    <div class="activity-content">
                        <div class="activity-icon">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <div class="activity-text">
                            <p><strong><?= htmlspecialchars($log['aktivitas']) ?></strong></p>
                            <span class="activity-time">
                                <i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($log['waktu'])) ?> WIB
                            </span>
                        </div>
                    </div>
                    <div class="activity-actions">
                        <button type="button" class="btn-delete" onclick="deleteLog(<?= $log['id_log'] ?>)">
                            <i class="fas fa-trash-alt"></i> Hapus
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Belum ada catatan aktivitas.</p>
            <?php if ($search || $filter_date): ?>
                <small style="color: #475569; margin-top: 10px; display: block;">Coba ubah filter pencarian Anda</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $filter_date ? '&filter_date=' . urlencode($filter_date) : '' ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        
        for ($i = $start; $i <= $end; $i++):
        ?>
            <?php if ($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $filter_date ? '&filter_date=' . urlencode($filter_date) : '' ?>">
                    <?= $i ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $filter_date ? '&filter_date=' . urlencode($filter_date) : '' ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function deleteLog(logId) {
    if (!confirm('Apakah Anda yakin ingin menghapus log ini?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', logId);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Terjadi kesalahan');
    });
}

function deleteAll() {
    if (!confirm('Apakah Anda yakin ingin menghapus SEMUA log aktivitas? Tindakan ini tidak dapat dibatalkan.')) {
        return;
    }

    if (!confirm('Konfirmasi sekali lagi: Hapus semua log?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_all');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Terjadi kesalahan');
    });
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
    `;
    
    if (type === 'success') {
        notification.style.background = 'rgba(34, 197, 94, 0.9)';
    } else {
        notification.style.background = 'rgba(239, 68, 68, 0.9)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php include '_footer_siswa.php'; ?>

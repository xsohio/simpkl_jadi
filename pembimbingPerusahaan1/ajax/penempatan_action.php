<?php
require_once '../config.php';
requireRole('pembimbing');
$pid = getPembimbingId();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../penempatan.php'); exit; }

$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['setujui','tolak'])) {
    $_SESSION['toast'] = ['msg'=>'Aksi tidak valid','type'=>'error'];
    header('Location: ../penempatan.php'); exit;
}

// Pastikan pengajuan ini milik pembimbing yang login
$check = $pdo->prepare("SELECT id FROM pkl_pengajuan WHERE id=? AND pembimbing_id=?");
$check->execute([$id,$pid]);
if (!$check->fetch()) {
    $_SESSION['toast'] = ['msg'=>'Akses ditolak','type'=>'error'];
    header('Location: ../penempatan.php'); exit;
}

$newStatus = $action === 'setujui' ? 'disetujui' : 'ditolak';
$stmt = $pdo->prepare("UPDATE pkl_pengajuan SET status_pembimbing=? WHERE id=?");
$stmt->execute([$newStatus, $id]);

$_SESSION['toast'] = ['msg'=>'Pengajuan berhasil '.($newStatus==='disetujui'?'disetujui':'ditolak').'!','type'=>$newStatus==='disetujui'?'success':'error'];
header('Location: ../penempatan.php');
exit;

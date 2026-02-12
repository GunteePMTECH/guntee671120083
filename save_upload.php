<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_upload'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file_upload'];
    
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // --- [Config ใหม่] ---
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_size = 10 * 1024 * 1024; // 10 MB (แก้ไขจาก 10GB)

    if (!in_array($file_ext, $allowed)) {
        echo "<script>alert('อนุญาตเฉพาะไฟล์รูปภาพและ PDF'); window.history.back();</script>"; exit;
    }
    if ($file_size > $max_size) {
        echo "<script>alert('ไฟล์มีขนาดใหญ่เกิน 10MB'); window.history.back();</script>"; exit;
    }

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $new_name = "evidence_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $sql = "INSERT INTO evidence (user_id, file_name, file_path) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $file['name'], $target_file);
        
        if ($stmt->execute()) {
            echo "<script>alert('อัปโหลดไฟล์สำเร็จ!'); window.location='dashboard_user.php';</script>";
        } else {
            echo "Database Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลด'); window.history.back();</script>";
    }
}
?>
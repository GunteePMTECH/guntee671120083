<?php
session_start();
require_once 'db.php'; // แก้ไข: เรียกไฟล์ db.php ที่อยู่ในโฟลเดอร์เดียวกัน

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // เตรียมคำสั่ง SQL เพื่อดึงข้อมูล user โดยหาจาก username
    // เราเลือก id, password, และ role มาใช้งาน
    $sql = "SELECT id, password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // ตรวจสอบรหัสผ่าน (เทียบกับ Hash ที่เก็บในฐานข้อมูล)
        if (password_verify($password, $row['password'])) {
            // Login สำเร็จ -> เก็บ Session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role']; // 'admin', 'user', 'evaluator'

            // --- [จุดที่แก้ไข]: แยกทางตาม Role เพิ่มเงื่อนไขให้ evaluator ---
            if ($row['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } elseif ($row['role'] == 'evaluator') {
                header("Location: dashboard_evaluator.php");
            } else {
                header("Location: dashboard_user.php");
            }
            // --------------------------------------------------------
            exit();
        } else {
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง'); window.location='login.html';</script>";
        }
    } else {
        echo "<script>alert('ไม่พบชื่อผู้ใช้งานนี้'); window.location='login.html';</script>";
    }
    
    $stmt->close();
    $conn->close();
} else {
    // ถ้าใครพยายามเข้าหน้านี้โดยไม่ได้กด Submit Form มา
    header("Location: login.html");
    exit();
}
?>
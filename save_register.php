<?php
require_once 'db.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

// ฟังก์ชันสำหรับแจ้งเตือนและเด้งกลับหน้าเดิม
function alert($msg, $redirect = 'register.html') {
    echo "<script>alert('$msg'); window.location.href='$redirect';</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. ตรวจสอบรหัสผ่าน
    if ($pass !== $confirm_pass) {
        alert("รหัสผ่านไม่ตรงกัน กรุณากรอกใหม่");
    }

    // 2. ตรวจสอบ Username / Email ซ้ำ
    $check_query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $user, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        alert("Username หรือ Email นี้ถูกใช้งานแล้ว");
    } else {
        // 3. เข้ารหัสและบันทึก
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $user, $email, $hashed_password);

        if ($stmt->execute()) {
            // สมัครเสร็จแล้ว ให้เด้งไปหน้า register.html หรือหน้า login ก็ได้
            alert("สมัครสมาชิกเรียบร้อยแล้ว!", "login.html"); 
        } else {
            alert("เกิดข้อผิดพลาด: " . $conn->error);
        }
    }
    $stmt->close();
    $conn->close();
}
?>
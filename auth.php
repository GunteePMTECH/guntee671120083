<?php
session_start();

// ฟังก์ชันเช็คว่า Login หรือยัง
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// ฟังก์ชันเช็คว่าเป็น Admin หรือไม่
function checkAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
    }
}

// ฟังก์ชันป้องกันการดูข้อมูลคนอื่น (IDOR)
function canViewEvaluation($conn, $eval_id, $current_user_id, $role) {
    if ($role === 'admin') return true;
    
    $sql = "SELECT evaluator_id, user_id FROM evaluations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eval_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // ยอมให้ดูถ้าเป็นคนประเมิน หรือ เป็นเจ้าของผลประเมินนั้น
    if ($result['evaluator_id'] == $current_user_id || $result['user_id'] == $current_user_id) {
        return true;
    }
    return false;
}
?>
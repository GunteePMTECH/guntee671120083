<?php
require_once 'auth.php';
require_once 'db.php';
checkLogin();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'evaluator') {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

$dashboard_url = ($_SESSION['role'] === 'admin') ? 'dashboard_admin.php' : 'dashboard_evaluator.php';
$target_id = $_GET['user_id'];
$user_sql = "SELECT username FROM users WHERE id = $target_id";
$target_user = $conn->query($user_sql)->fetch_assoc();

$evidence_sql = "SELECT * FROM evidence WHERE user_id = $target_id AND file_path NOT LIKE '%/ADMIN_EVID_%' AND file_path NOT LIKE '%/EVAL_EVID_%' ORDER BY uploaded_at DESC";
$evidence = $conn->query($evidence_sql);

// ฟังก์ชันเช็คไฟล์ภาพ
function is_preview_image($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate User | HR Portal Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        /* --- 1. Background System --- */
        body {
            height: auto;
            min-height: 100vh;
            margin: 0;
            padding: 40px 20px;
            background-color: #f0f2f5;
            background-image: 
                radial-gradient(at 0% 0%, rgba(161, 196, 253, 0.4) 0, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(212, 252, 121, 0.3) 0, transparent 50%),
                radial-gradient(at 100% 0%, rgba(255, 154, 158, 0.3) 0, transparent 50%);
            font-family: 'Kanit', sans-serif;
        }

        /* --- 2. Advanced Workspace Container --- */
        .container-wide {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(25px) saturate(180%);
            padding: 50px;
            width: 100%;
            max-width: 1100px;
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 30px 60px rgba(0,0,0,0.08);
            margin: 0 auto;
            color: #2d3748;
            position: relative;
            
            /* จุดสำคัญ: แก้ไขขอบสีรุ้งล้นด้วย overflow: hidden */
            overflow: hidden; 
        }

        /* แถบสีรุ้งด้านบน */
        .container-wide::before {
            content: '';
            position: absolute;
            top: 0; 
            left: 0; 
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #bc8cff, #a1c4fd, #fad0c4);
            z-index: 10;
        }

        h2 { color: #2d3748; font-weight: 600; font-size: 26px; margin-bottom: 5px; }
        .user-focus { color: #72a1ed; font-weight: 600; font-size: 1.2rem; }

        /* --- 3. Evidence Gallery --- */
        .evidence-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .evidence-item {
            background: white;
            padding: 10px;
            border-radius: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.05);
            text-align: center;
            transition: all 0.3s ease;
        }

        .evidence-item:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }

        .preview-box {
            width: 100%;
            height: 140px;
            border-radius: 18px;
            overflow: hidden;
            background: #f7fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .preview-img { width: 100%; height: 100%; object-fit: cover; }
        .file-icon { font-size: 40px; }

        /* --- 4. Table Styling --- */
        .table-wrapper {
            overflow: hidden;
            margin: 25px 0 35px 0;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .dev-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .dev-table th { background: rgba(114, 161, 237, 0.1); color: #4a5568; padding: 20px; text-align: left; }
        .dev-table td { padding: 18px 20px; border-bottom: 1px solid rgba(0,0,0,0.03); }

        .score-input {
            width: 70px; background: #fff; border: 2px solid #edf2f7;
            padding: 10px; border-radius: 15px; text-align: center; font-weight: 600;
        }

        /* --- 5. UI Elements --- */
        input[type="file"] { display: none; }
        .custom-file-upload {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 20px; border: 2px dashed #a1c4fd; border-radius: 25px;
            background: rgba(255, 255, 255, 0.3); cursor: pointer; height: 120px;
        }

        .btn {
            padding: 16px 24px; border-radius: 18px; border: none;
            cursor: pointer; font-weight: 600; transition: all 0.3s;
            text-decoration: none; text-align: center; font-size: 14px;
        }
        .btn-primary { 
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); 
            color: white; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(59, 130, 246, 0.4); }

        .btn-back {
            background: #fff; color: #4a5568;
            border: 1px solid #e2e8f0;
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 12px; margin-bottom: 20px;
        }
        .btn-back:hover { background: #f7fafc; }

        textarea {
            width: 100%; height: 120px; border: 2px solid #edf2f7;
            border-radius: 20px; padding: 15px; resize: none;
        }

        .badge { padding: 6px 12px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        .badge-functional { background: #e0f2fe; color: #0369a1; }
        .badge-security { background: #fee2e2; color: #b91c1c; }
        .badge-nonfunc { background: #f0fdf4; color: #15803d; }
        .code-font { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body>
    <div class="container-wide">
        <a href="<?php echo $dashboard_url; ?>" class="btn-back">
            ⬅ กลับไปยัง Dashboard
        </a>

        <div class="code-font" style="color: #718096; letter-spacing: 1.5px; margin-bottom: 10px;">● WORKSPACE_ACTIVE / EVAL_MODE</div>
        <h2>ประเมินผล: <span class="user-focus"><?php echo htmlspecialchars($target_user['username']); ?></span></h2>
        
        <div style="margin: 25px 0; padding: 25px; background: rgba(255,255,255,0.3); border-radius: 35px; border: 1px solid rgba(255,255,255,0.5);">
            <div class="code-font" style="color: #a1c4fd; margin-bottom: 15px; font-weight: 600;">> USER_SUBMITTED_FILES</div>
            <div class="evidence-gallery">
                <?php if ($evidence->num_rows == 0): ?>
                    <p style='color:#a0aec0; grid-column: 1/-1; text-align: center; padding: 20px;'>-- ไม่พบหลักฐานในระบบ --</p>
                <?php else: ?>
                    <?php while($file = $evidence->fetch_assoc()): ?>
                        <div class="evidence-item">
                            <div class="preview-box">
                                <?php if (is_preview_image($file['file_path'])): ?>
                                    <a href="<?php echo $file['file_path']; ?>" target="_blank">
                                        <img src="<?php echo $file['file_path']; ?>" class="preview-img" alt="Preview">
                                    </a>
                                <?php else: ?>
                                    <span class="file-icon">📄</span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $file['file_path']; ?>" target="_blank" style="color: #4a5568; text-decoration: none; font-size: 11px; font-weight: 600; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($file['file_name']); ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

        <form action="save_evaluation.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
            <input type="hidden" name="score" id="hidden_total_score" value="0">

            <div class="code-font" style="color: #2d3748; margin-bottom: 10px; font-weight: 600;">> EVALUATION_MATRIX</div>
            <div class="table-wrapper">
                <table class="dev-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Category</th>
                            <th style="width: 20%;">Criteria</th>
                            <th style="width: 45%;">Expected Outcome</th> 
                            <th style="text-align: center; width: 20%;">Score (0-10)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $test_cases = [
                            ["Functional", "ระบบสมาชิก", "ทดสอบสมัครสมาชิกและ Login", "เข้าสู่ระบบสำเร็จ / ข้อมูลถูกต้อง", "badge-functional"],
                            ["Functional", "การแสดงผลหน้าแรก", "เข้าหน้าแรกด้วยสิทธิ์ Admin/Evaluator/User", "เห็นเมนูถูกต้องตามสิทธิ์ตัวเอง", "badge-functional"],
                            ["Functional", "ดูคะแนนตัวเอง", "User เข้าไปดูผลคะแนน", "เห็นเฉพาะคะแนนของตัวเองเท่านั้น", "badge-functional"],
                            ["Functional", "การบันทึกข้อมูล", "ลองกรอกข้อมูลหรือกดบันทึกซ้ำๆ", "ระบบป้องกันการบันทึกซ้ำ", "badge-functional"],
                            ["Security", "แอบดูข้อมูลคนอื่น", "ลองเปลี่ยน ID ใน URL เพื่อดูข้อมูลคนอื่น", "ระบบบล็อค (Access Denied)", "badge-security"],
                            ["Security", "การจัดการไฟล์", "ลองลบไฟล์สำคัญออกจากระบบ", "ลบไม่ได้ / ระบบแจ้งเตือน", "badge-security"],
                            ["Non-functional", "ขนาดไฟล์", "อัปโหลดไฟล์ขนาดใหญ่เกิน 10MB", "แจ้งเตือน 'ไฟล์ใหญ่เกินไป'", "badge-nonfunc"],
                            ["Non-functional", "ประเภทไฟล์", "อัปโหลดไฟล์แปลกๆ เช่น .exe", "แจ้งเตือน 'นามสกุลไฟล์ไม่ถูกต้อง'", "badge-nonfunc"],
                            ["Functional", "ดาวน์โหลดรายงาน", "ทดลองกดปุ่มดาวน์โหลด/เปิดไฟล์", "ไฟล์เปิดได้ถูกต้อง ไม่เสียหาย", "badge-functional"],
                            ["Security", "ความปลอดภัย Login", "ลองใส่รหัสแปลกๆ (' OR 1=1) หน้า Login", "เข้าสู่ระบบไม่ได้ / ระบบปลอดภัย", "badge-security"]
                        ];
                        
                        foreach ($test_cases as $tc) {
                            echo "<tr>
                                    <td><span class='badge {$tc[4]}'>{$tc[0]}</span></td>
                                    <td>{$tc[1]}</td>
                                    <td><small style='color:#718096'>{$tc[2]}</small><br>{$tc[3]}</td>
                                    <td style='text-align: center;'>
                                        <input type='number' name='item_scores[]' class='score-input' min='0' max='10' value='0' oninput='calculateTotal()' required>
                                    </td>
                                  </tr>";
                        }
                        ?>
                        <tr style="background: rgba(114, 161, 237, 0.1);">
                            <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL CALCULATED:</td>
                            <td style="text-align: center; font-weight: bold; font-size: 18px; color: #3182ce;" id="total-display">0 / 100</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div class="input-group">
                    <label style="font-weight: 600; font-size: 14px; display: block; margin-bottom: 12px;">📎 FEEDBACK REPORT (PDF/IMG):</label>
                    <label for="admin_file" class="custom-file-upload">
                        <span style="font-size: 30px;">📤</span>
                        <span style="font-size: 13px; font-weight: 600; margin-top:5px;">UPLOAD EVALUATION FILE</span>
                        <small id="admin-file-name" style="color:#a0aec0; margin-top:8px;" class="code-font">READY_FOR_ATTACHMENT</small>
                    </label>
                    <input type="file" name="admin_evidence_file" id="admin_file" onchange="showFileName(this, 'admin-file-name')">
                </div>

                <div class="input-group">
                    <label style="font-weight: 600; font-size: 14px; display: block; margin-bottom: 12px;">💬 EVALUATOR REMARKS:</label>
                    <textarea name="comment" placeholder="กรอกข้อเสนอแนะหรือเหตุผลการให้คะแนน..."></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 20px; margin-top: 40px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; font-size: 16px;">
                    ✅ ยืนยันผลการประเมิน (SUBMIT EVALUATION)
                </button>
            </div>
        </form>
    </div>

    <script>
        function calculateTotal() {
            let inputs = document.querySelectorAll('.score-input');
            let total = 0;
            inputs.forEach(input => {
                let val = parseInt(input.value) || 0;
                if (val > 10) val = 10;
                if (val < 0) val = 0;
                input.value = val;
                total += val;
            });
            document.getElementById('total-display').innerText = total + " / 100";
            document.getElementById('hidden_total_score').value = total;
        }

        function showFileName(input, displayId) {
            const display = document.getElementById(displayId);
            if (input.files && input.files.length > 0) {
                display.innerText = "SELECTED: " + input.files[0].name;
                display.style.color = "#38a169";
            } else {
                display.innerText = "READY_FOR_ATTACHMENT";
                display.style.color = "#a0aec0";
            }
        }
    </script>
</body>
</html>
<?php
date_default_timezone_set('Asia/Bangkok'); 

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_start();
    session_unset();
    session_destroy();
    header("Location: index.html");
    exit();
}

require_once 'auth.php';
require_once 'db.php';
checkLogin();

$user_id = $_SESSION['user_id'];

// ดึงไฟล์แบ่งหมวดหมู่
$admin_files = $conn->query("SELECT * FROM evidence WHERE user_id = $user_id AND file_path LIKE '%/ADMIN_EVID_%' ORDER BY uploaded_at DESC");
$eval_files = $conn->query("SELECT * FROM evidence WHERE user_id = $user_id AND file_path LIKE '%/EVAL_EVID_%' ORDER BY uploaded_at DESC");
$user_files = $conn->query("SELECT * FROM evidence WHERE user_id = $user_id AND file_path NOT LIKE '%/ADMIN_EVID_%' AND file_path NOT LIKE '%/EVAL_EVID_%' ORDER BY uploaded_at DESC");

// ดึงคะแนน
$score_sql = "SELECT e.score, e.comments, u.role as evaluator_role FROM evaluations e JOIN users u ON e.evaluator_id = u.id WHERE e.user_id = $user_id";
$score_result = $conn->query($score_sql);
$admin_score = null; $evaluator_score = null;
while($row = $score_result->fetch_assoc()) {
    if ($row['evaluator_role'] == 'admin') $admin_score = $row;
    elseif ($row['evaluator_role'] == 'evaluator') $evaluator_score = $row;
}

// ฟังก์ชันสำหรับเช็คว่าเป็นรูปภาพหรือไม่
function is_image($filename) {
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_extensions);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal | HR System</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        /* --- 1. Premium Mesh Background --- */
        body {
            margin: 0; padding: 0; min-height: 100vh;
            background-color: #e0e5ec;
            background-image: 
                radial-gradient(at 0% 0%, rgba(161, 196, 253, 0.4) 0, transparent 50%), 
                radial-gradient(at 100% 0%, rgba(250, 208, 196, 0.4) 0, transparent 50%), 
                radial-gradient(at 50% 100%, rgba(212, 252, 121, 0.3) 0, transparent 50%),
                radial-gradient(at 0% 100%, rgba(255, 154, 158, 0.3) 0, transparent 50%);
            font-family: 'Kanit', sans-serif;
            display: flex; justify-content: center; align-items: flex-start;
            padding: 50px 20px;
        }

        /* --- 2. Enhanced Glassmorphism Card --- */
        .container {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(25px) saturate(160%);
            -webkit-backdrop-filter: blur(25px) saturate(160%);
            padding: 60px 50px;
            width: 100%;
            max-width: 1100px;
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 25px 50px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden; /* แก้ไขปัญหาเส้นรุ้งล้นขอบ */
        }

        /* เส้นรุ้งพาสเทลด้านบน */
        .container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ff9a9e, #fad0c4, #a1c4fd, #c2e9fb, #d4fc79);
            border-radius: 40px 40px 0 0;
            z-index: 10;
        }

        h2 { font-size: 32px; color: #2d3748; margin-bottom: 40px; text-align: center; font-weight: 600; }

        /* --- 3. Score Section --- */
        .score-row { display: flex; gap: 25px; margin-bottom: 50px; flex-wrap: wrap; }
        .score-card {
            flex: 1; min-width: 280px; 
            background: rgba(255, 255, 255, 0.6);
            padding: 35px;
            border-radius: 30px; 
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            transition: 0.3s;
        }
        .score-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.8); }
        .score-card h1 { font-size: 64px; margin: 10px 0; color: #1a202c; font-weight: 600; }
        .score-label { 
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600; font-size: 12px; letter-spacing: 1px; text-transform: uppercase;
        }

        /* --- 4. Upload Section --- */
        .upload-area { margin-bottom: 50px; }
        .upload-label-header { 
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px; font-weight: bold; color: #718096; 
            margin-bottom: 12px; display: block; margin-left: 10px; 
        }
        .upload-zone {
            background: rgba(255, 255, 255, 0.3);
            border: 2px dashed #a1c4fd;
            border-radius: 30px;
            padding: 50px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-zone:hover { background: rgba(255, 255, 255, 0.6); border-color: #5477f5; }
        .cloud-icon { font-size: 40px; margin-bottom: 15px; display: block; filter: grayscale(0.2); }
        .upload-text { font-size: 16px; color: #4a5568; font-weight: 400; }

        .btn-submit-pill {
            display: block; width: fit-content; margin: 25px auto 0;
            background: linear-gradient(135deg, #72a1ed 0%, #5477f5 100%);
            color: white; border: none;
            padding: 16px 50px; border-radius: 20px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            box-shadow: 0 10px 20px rgba(84, 119, 245, 0.3);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-submit-pill:hover { transform: scale(1.05); box-shadow: 0 15px 30px rgba(84, 119, 245, 0.4); }

        /* --- 5. Evidence Section --- */
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .evidence-col { 
            background: rgba(255, 255, 255, 0.3); 
            padding: 30px; border-radius: 35px; 
            border: 1px solid rgba(255, 255, 255, 0.5); 
        }
        
        .file-card {
            background: rgba(255, 255, 255, 0.8); 
            padding: 18px; 
            border-radius: 25px; 
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 8px 20px rgba(0,0,0,0.02);
            transition: 0.3s ease;
        }
        .file-card:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0,0,0,0.05); }

        .img-preview { 
            width: 100%; height: 180px; object-fit: cover; 
            border-radius: 20px; margin-bottom: 15px;
            border: 2px solid #fff;
        }

        /* --- 6. Logout Button --- */
        .logout-container { margin-top: 60px; display: flex; justify-content: center; }
        .btn-logout {
            background: rgba(255, 255, 255, 0.5);
            color: #e53e3e;
            padding: 12px 30px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 600; font-size: 14px;
            transition: all 0.3s;
            border: 1px solid rgba(229, 62, 62, 0.1);
            display: flex; align-items: center; gap: 10px;
        }
        .btn-logout:hover {
            background: #fff5f5;
            border-color: #fc8181;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 62, 62, 0.1);
        }

        input[type="file"] { display: none; }
        #file-name { color: #5477f5; font-weight: 600; margin-top: 12px; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Performance Portal</h2>

    <div class="score-row">
        <div class="score-card" style="border-top: 5px solid #a1c4fd;">
            <div class="score-label" style="color: #72a1ed;">+ ADMIN_SCORE</div>
            <?php if ($admin_score): ?>
                <h1><?php echo $admin_score['score']; ?><span style="font-size: 20px; color: #cbd5e0;">/100</span></h1>
                <p style="color: #4a5568; font-size: 14px; font-style: italic;">"<?php echo htmlspecialchars($admin_score['comments']); ?>"</p>
            <?php else: ?>
                <h1 style="color: #e2e8f0;">--</h1>
                <p style="color: #a0aec0; font-size: 14px;">รอการประเมินจากผู้ดูแล</p>
            <?php endif; ?>
        </div>

        <div class="score-card" style="border-top: 5px solid #bc8cff;">
            <div class="score-label" style="color: #bc8cff;">+ EVALUATOR_SCORE</div>
            <?php if ($evaluator_score): ?>
                <h1><?php echo $evaluator_score['score']; ?><span style="font-size: 20px; color: #cbd5e0;">/100</span></h1>
                <p style="color: #4a5568; font-size: 14px; font-style: italic;">"<?php echo htmlspecialchars($evaluator_score['comments']); ?>"</p>
            <?php else: ?>
                <h1 style="color: #e2e8f0;">--</h1>
                <p style="color: #a0aec0; font-size: 14px;">รอการประเมินจากหัวหน้า</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="upload-area">
        <span class="upload-label-header">+ UPLOAD_EVIDENCE</span>
        <form action="save_upload.php" method="post" enctype="multipart/form-data">
            <div class="upload-zone" onclick="document.getElementById('user_file').click()" id="drop-zone">
                <span class="cloud-icon">☁️</span>
                <div class="upload-text" id="instruction">Drag & Drop หรือคลิกเพื่อเลือกไฟล์หลักฐาน</div>
                <div id="file-name" style="display:none;"></div>
                <input type="file" name="file_upload" id="user_file" required onchange="updateFile(this)">
            </div>
            <button type="submit" class="btn-submit-pill">Submit Document</button>
        </form>
    </div>

    <div class="evidence-grid">
        <div class="evidence-col">
            <div style="font-weight: 600; color: #72a1ed; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-family: 'JetBrains Mono', monospace; font-size: 13px;">
                <span style="font-size: 20px;">📄</span> ADMIN FEEDBACK
            </div>
            <?php if ($admin_files->num_rows > 0): ?>
                <?php while($f = $admin_files->fetch_assoc()): ?>
                    <div class="file-card">
                        <?php if (is_image($f['file_path'])): ?>
                            <a href="<?php echo $f['file_path']; ?>" target="_blank">
                                <img src="<?php echo $f['file_path']; ?>" class="img-preview" alt="Admin Feedback">
                            </a>
                        <?php endif; ?>
                        <div style="font-size: 11px; color: #a0aec0; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace;"><?php echo date('d M Y', strtotime($f['uploaded_at'])); ?></div>
                        <div style="font-weight: 500; font-size: 14px; color: #2d3748; margin-bottom: 12px; word-break: break-all;"><?php echo $f['file_name']; ?></div>
                        <a href="<?php echo $f['file_path']; ?>" target="_blank" style="color: #5477f5; font-size: 13px; text-decoration: none; font-weight: 600;">View Full File →</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color: #a0aec0; font-size: 13px;">ไม่มีไฟล์จาก Admin</p>
            <?php endif; ?>
        </div>

        <div class="evidence-col">
            <div style="font-weight: 600; color: #bc8cff; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-family: 'JetBrains Mono', monospace; font-size: 13px;">
                <span style="font-size: 20px;">✨</span> EVALUATOR FEEDBACK
            </div>
            <?php if ($eval_files->num_rows > 0): ?>
                <?php while($f = $eval_files->fetch_assoc()): ?>
                    <div class="file-card">
                        <?php if (is_image($f['file_path'])): ?>
                            <a href="<?php echo $f['file_path']; ?>" target="_blank">
                                <img src="<?php echo $f['file_path']; ?>" class="img-preview" alt="Evaluator Feedback">
                            </a>
                        <?php endif; ?>
                        <div style="font-size: 11px; color: #a0aec0; margin-bottom: 5px; font-family: 'JetBrains Mono', monospace;"><?php echo date('d M Y', strtotime($f['uploaded_at'])); ?></div>
                        <div style="font-weight: 500; font-size: 14px; color: #2d3748; margin-bottom: 12px; word-break: break-all;"><?php echo $f['file_name']; ?></div>
                        <a href="<?php echo $f['file_path']; ?>" target="_blank" style="color: #bc8cff; font-size: 13px; text-decoration: none; font-weight: 600;">View Full File →</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color: #a0aec0; font-size: 13px;">ไม่มีไฟล์จาก Evaluator</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="logout-container">
        <a href="?action=logout" class="btn-logout" onclick="return confirm('คุณแน่ใจว่าต้องการออกจากระบบ?')">
            <span>🚪</span> SIGN OUT SYSTEM
        </a>
    </div>
</div>

<script>
    function updateFile(input) {
        const nameDisplay = document.getElementById('file-name');
        const instruction = document.getElementById('instruction');
        const dropZone = document.getElementById('drop-zone');
        if (input.files.length > 0) {
            instruction.style.display = 'none';
            nameDisplay.innerText = "📄 READY TO UPLOAD: " + input.files[0].name;
            nameDisplay.style.display = 'block';
            dropZone.style.background = "rgba(232, 245, 233, 0.6)";
            dropZone.style.borderColor = "#66bb6a";
        }
    }
</script>

</body>
</html>
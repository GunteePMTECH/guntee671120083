<?php
// --- ระบบ Logout แบบซ่อนในตัว ---
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
checkAdmin(); 

$admin_id = $_SESSION['user_id'];

// ดึงเฉพาะคนที่เป็น 'user'
$sql = "SELECT u.id, u.username, u.email, e.score 
        FROM users u 
        LEFT JOIN evaluations e ON u.id = e.user_id AND e.evaluator_id = $admin_id
        WHERE u.role = 'user'";
$result = $conn->query($sql);

if (!$result) {
    die("<div style='background: white; color: red; padding: 20px; font-family: sans-serif;'>
        <strong>เกิดข้อผิดพลาดในการดึงข้อมูล (SQL Error):</strong><br> " . $conn->error . "
        </div>");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center | HR Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        /* --- 1. Premium Mesh Background (ธีมเดียวกับทั้งระบบ) --- */
        body {
            margin: 0;
            padding: 60px 20px;
            min-height: 100vh;
            background-color: #e0e5ec;
            background-image: 
                radial-gradient(at 0% 0%, rgba(161, 196, 253, 0.4) 0, transparent 50%), 
                radial-gradient(at 100% 0%, rgba(250, 208, 196, 0.4) 0, transparent 50%), 
                radial-gradient(at 50% 100%, rgba(212, 252, 121, 0.3) 0, transparent 50%),
                radial-gradient(at 0% 100%, rgba(255, 154, 158, 0.3) 0, transparent 50%);
            font-family: 'Kanit', sans-serif;
        }

        /* --- 2. Advanced Glassmorphism Container --- */
        .container-wide {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(25px) saturate(160%);
            -webkit-backdrop-filter: blur(25px) saturate(160%);
            padding: 50px;
            width: 100%;
            max-width: 1000px;
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 25px 50px rgba(0,0,0,0.06);
            position: relative;
            margin: 0 auto;
            overflow: hidden; /* ล็อคเส้นรุ้งให้มนตามกล่อง */
        }

        /* แถบรุ้งพาสเทลระดับพรีเมียม */
        .container-wide::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #ff9a9e, #fad0c4, #a1c4fd, #c2e9fb, #d4fc79);
            border-radius: 40px 40px 0 0;
            z-index: 10;
        }

        h2 { color: #2d3748; font-weight: 600; margin-top: 10px; font-size: 28px; }
        .sub-text { color: #718096; margin-bottom: 35px; font-size: 15px; }

        /* --- 3. Modern Table Design --- */
        .table-wrapper {
            overflow-x: auto;
            margin: 30px 0;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }

        .dev-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        .dev-table th { 
            background: rgba(255, 255, 255, 0.6);
            color: #4a5568; 
            padding: 20px; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 11px;
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .dev-table td { 
            padding: 20px; 
            border-bottom: 1px solid rgba(0,0,0,0.02); 
            color: #2d3748; 
            vertical-align: middle; 
        }

        .dev-table tr:hover td { background-color: rgba(255, 255, 255, 0.8); }
        .dev-table tr:last-child td { border-bottom: none; }

        /* --- 4. Badges & Buttons --- */
        .status-badge { 
            padding: 6px 14px; 
            border-radius: 12px; 
            font-size: 11px; 
            font-weight: 600; 
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-pending { background: #fff5f5; color: #e53e3e; border: 1px solid #fed7d7; }
        .status-done { background: #f0fff4; color: #38a169; border: 1px solid #c6f6d5; }

        .btn {
            border-radius: 14px;
            padding: 10px 18px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: center;
            font-size: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #a1c4fd 0%, #72a1ed 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(161, 196, 253, 0.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(161, 196, 253, 0.5); }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.7);
            color: #718096;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .btn-secondary:hover { background: white; color: #2d3748; transform: translateY(-2px); }

        .btn-logout {
            background: #fff5f5;
            color: #e53e3e;
            border: 1px solid #feb2b2;
            padding: 12px 30px;
            margin-top: 30px;
        }
        .btn-logout:hover { background: #e53e3e; color: white; }

        .code-font { font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container-wide">
        <div class="code-font" style="color: #72a1ed; letter-spacing: 2px;">● SYSTEM_ADMIN_CORE</div>
        <h2>แดชบอร์ด <span style="color:#72a1ed">ผู้จัดการระบบ</span></h2>
        <p class="sub-text">Monitor and evaluate workforce performance.</p>

        <div class="table-wrapper">
            <table class="dev-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 40%;">ข้อมูลผู้ใช้งาน</th>
                        <th style="width: 25%;">สถานะการประเมิน</th>
                        <th style="width: 25%;">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        
                        <?php $is_evaluated = !is_null($row['score']); ?>
                        
                        <tr>
                            <td class="code-font" style="color: #bc8cff;">#<?php echo sprintf("%03d", $row['id']); ?></td>
                            <td>
                                <div style="font-weight: 600; color: #2d3748; font-size: 15px;"><?php echo htmlspecialchars($row['username']); ?></div>
                                <div class="code-font" style="color: #a0aec0; margin-top: 3px; font-weight: 400;">
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if($is_evaluated): ?>
                                    <span class="status-badge status-done">
                                        <span>✓</span> ประเมินแล้ว (<?php echo $row['score']; ?>/100)
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">
                                        <span>●</span> รอการดำเนินการ
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($is_evaluated): ?>
                                    <a href="evaluation.php?user_id=<?php echo $row['id']; ?>" class="btn btn-secondary">
                                        EDIT EVAL
                                    </a>
                                <?php else: ?>
                                    <a href="evaluation.php?user_id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                        START EVAL
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #a0aec0;">
                                <div style="font-size: 24px; margin-bottom: 10px;">📂</div>
                                <div class="code-font">-- NO_USER_DATA_FOUND --</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center;">
            <a href="?action=logout" class="btn btn-logout" onclick="return confirm('ต้องการออกจากระบบการจัดการหรือไม่?')">
                EXIT COMMAND CENTER
            </a>
        </div>
    </div>
</body>
</html>
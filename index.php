<?php
/**
 * 🌸 Sakura Binary Tree Garden (PHP Version)
 * เชื่อมต่อกับ MariaDB ผ่าน Environment Variables จาก Dokploy
 */

// 1. ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'treedb';

$conn = new mysqli($host, $user, $pass, $db);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    // ถ้าเชื่อมไม่ได้จะยังทำงานต่อได้ (เผื่อรันในเครื่อง) แต่แจ้งเตือนเบาๆ
    $db_status = "Disconnected: " . $conn->connect_error;
} else {
    $db_status = "Connected to MariaDB 🌸";
}

// 2. จัดการเมื่อมีการส่งข้อมูล (ปลูก Node)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['node_val'])) {
    $val = intval($_POST['node_val']);
    $stmt = $conn->prepare("INSERT INTO sakura_nodes (node_value) VALUES (?)");
    $stmt->bind_param("i", $val);
    $stmt->execute();
    $stmt->close();
    
    // Redirect เพื่อป้องกันการส่งข้อมูลซ้ำเมื่อกด Refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 3. จัดการเมื่อกด "ล้างสวน"
if (isset($_POST['reset_tree'])) {
    $conn->query("TRUNCATE TABLE sakura_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลโหนดทั้งหมดจากฐานข้อมูล
$db_nodes = [];
$result = $conn->query("SELECT node_value FROM sakura_nodes ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $db_nodes[] = (int)$row['node_value'];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sakura Binary Tree Garden (Database Version)</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Tahoma', sans-serif;
            background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%);
            overflow-y: auto;
            padding: 20px 0;
        }

        .container {
            z-index: 10;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: 40px;
            box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2);
            text-align: center;
            width: 90%;
            max-width: 900px;
        }

        h1 { color: #d81b60; margin-bottom: 5px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .db-badge { font-size: 10px; color: #ad1457; margin-bottom: 20px; display: block; }

        .controls { margin-bottom: 20px; }

        input {
            padding: 12px;
            border: 2px solid #ffb6c1;
            border-radius: 15px;
            width: 80px;
            outline: none;
            font-size: 16px;
            text-align: center;
        }

        button {
            padding: 12px 25px;
            background: #ff69b4;
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
            margin-left: 5px;
        }

        button:hover { background: #e91e63; transform: scale(1.05); }

        canvas {
            background: rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            border: 2px solid #fff;
            max-width: 100%;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .result-panel, .guide-panel {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 20px;
            text-align: left;
            border: 1px solid #f8bbd0;
        }

        .panel-title {
            font-weight: bold;
            color: #d81b60;
            margin-bottom: 10px;
            display: block;
            border-bottom: 2px solid #ffdee9;
            padding-bottom: 5px;
        }

        .order-row { margin: 8px 0; font-size: 14px; color: #444; word-break: break-all; }
        .tag { font-weight: bold; color: #ad1457; display: inline-block; width: 90px; }
        
        .guide-item {
            margin-bottom: 8px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
        }

        .dot {
            height: 10px;
            width: 10px;
            background-color: #8bc34a;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura Tree Garden 🌸</h1>
    <span class="db-badge"><?php echo $db_status; ?></span>
    
    <div class="controls">
        <form method="POST" style="display: inline-block;">
            <input type="number" name="node_val" placeholder="เลข" required>
            <button type="submit">ปลูก Node</button>
        </form>
        
        <form method="POST" style="display: inline-block;">
            <input type="hidden" name="reset_tree" value="1">
            <button type="submit" style="background:#90a4ae">ล้างสวน</button>
        </form>
    </div>

    <canvas id="treeCanvas" width="800" height="400"></canvas>

    <div class="info-grid">
        <div class="result-panel">
            <span class="panel-title">ลำดับที่ได้ (จากฐานข้อมูล):</span>
            <div class="order-row"><span class="tag">Preorder:</span> <span id="preText">-</span></div>
            <div class="order-row"><span class="tag">Inorder:</span> <span id="inText">-</span></div>
            <div class="order-row"><span class="tag">Postorder:</span> <span id="postText">-</span></div>
        </div>

        <div class="guide-panel">
            <span class="panel-title">หลักการ Binary Search Tree:</span>
            <div class="guide-item"><span class="dot"></span> น้อยกว่า Root ไปทาง <strong>ซ้าย (Left)</strong></div>
            <div class="guide-item"><span class="dot"></span> มากกว่า Root ไปทาง <strong>ขวา (Right)</strong></div>
            <div class="guide-item"><span class="dot" style="background:#ff69b4"></span> บันทึกข้อมูลลงใน <strong>MariaDB</strong></div>
        </div>
    </div>
</div>

<script>
    class Node {
        constructor(val) {
            this.val = val;
            this.left = null;
            this.right = null;
        }
    }

    // รับข้อมูลจาก PHP ที่ดึงมาจาก Database
    const initialNodes = <?php echo json_encode($db_nodes); ?>;
    let root = null;
    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

    // ฟังก์ชันสร้างต้นไม้จากข้อมูลใน DB
    function buildTreeFromDB() {
        if (initialNodes.length === 0) return;
        initialNodes.forEach(val => {
            if (!root) root = new Node(val);
            else insertNode(root, val);
        });
        render();
    }

    function insertNode(node, val) {
        if (val < node.val) {
            if (!node.left) node.left = new Node(val);
            else insertNode(node.left, val);
        } else if (val > node.val) {
            if (!node.right) node.right = new Node(val);
            else insertNode(node.right, val);
        }
    }

    function render() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (root) drawTree(root, canvas.width / 2, 50, 180);
        updateTraversalText();
    }

    function drawTree(node, x, y, space) {
        if (node.left) {
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x - space, y + 70);
            ctx.strokeStyle = '#ffb6c1';
            ctx.lineWidth = 3;
            ctx.stroke();
            drawTree(node.left, x - space, y + 70, space / 1.9);
        }
        if (node.right) {
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x + space, y + 70);
            ctx.strokeStyle = '#ffb6c1';
            ctx.lineWidth = 3;
            ctx.stroke();
            drawTree(node.right, x + space, y + 70, space / 1.9);
        }

        ctx.beginPath();
        ctx.arc(x, y, 20, 0, Math.PI * 2);
        ctx.fillStyle = "white";
        ctx.fill();
        ctx.strokeStyle = "#ff69b4";
        ctx.lineWidth = 2;
        ctx.stroke();
        
        ctx.fillStyle = "#ad1457";
        ctx.font = "bold 14px Arial";
        ctx.textAlign = "center";
        ctx.fillText(node.val, x, y + 5);
    }

    function getPreorder(n, r=[]) { if(n){ r.push(n.val); getPreorder(n.left, r); getPreorder(n.right, r); } return r; }
    function getInorder(n, r=[]) { if(n){ getInorder(n.left, r); r.push(n.val); getInorder(n.right, r); } return r; }
    function getPostorder(n, r=[]) { if(n){ getPostorder(n.left, r); getPostorder(n.right, r); r.push(n.val); } return r; }

    function updateTraversalText() {
        document.getElementById('preText').innerText = getPreorder(root).join(' - ') || '-';
        document.getElementById('inText').innerText = getInorder(root).join(' - ') || '-';
        document.getElementById('postText').innerText = getPostorder(root).join(' - ') || '-';
    }

    // เริ่มสร้างต้นไม้เมื่อโหลดหน้าเว็บ
    buildTreeFromDB();
</script>

</body>
</html>


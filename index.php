<?php
// --- 1. การตั้งค่าการเชื่อมต่อฐานข้อมูล ---
$host = 'localhost';
$db   = 'sakura_garden';
$user = 'root'; // ตามที่ตั้งค่าใน XAMPP/WAMP
$pass = '';     // ปกติจะว่างเปล่าใน XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // สร้าง Table อัตโนมัติถ้ายังไม่มี
    $pdo->exec("CREATE TABLE IF NOT EXISTS tree_nodes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        node_value INT NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (\PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $e->getMessage());
}

// --- 2. ส่วนของ Logic PHP (API เล็กๆ ในไฟล์เดียว) ---
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add' && isset($_POST['val'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tree_nodes (node_value) VALUES (?)");
            $stmt->execute([$_POST['val']]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) { echo json_encode(['status' => 'error']); }
        exit;
    }
    if ($_GET['action'] == 'clear') {
        $pdo->exec("TRUNCATE TABLE tree_nodes");
        echo json_encode(['status' => 'success']);
        exit;
    }
    if ($_GET['action'] == 'get') {
        $stmt = $pdo->query("SELECT node_value FROM tree_nodes ORDER BY id ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sakura Tree Garden Database</title>
    <style>
        /* CSS เดิมของคุณทั้งหมด */
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); overflow-y: auto; padding: 20px 0; }
        .container { z-index: 10; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); padding: 25px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 90%; max-width: 900px; }
        h1 { color: #d81b60; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .controls { margin-bottom: 20px; }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 80px; outline: none; font-size: 16px; text-align: center; }
        button { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: 0.3s; margin-left: 5px; }
        button:hover { background: #e91e63; transform: scale(1.05); }
        canvas { background: rgba(255, 255, 255, 0.4); border-radius: 20px; border: 2px solid #fff; max-width: 100%; box-shadow: inset 0 0 10px rgba(0,0,0,0.05); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .result-panel, .guide-panel { background: rgba(255, 255, 255, 0.9); padding: 15px; border-radius: 20px; text-align: left; border: 1px solid #f8bbd0; }
        .panel-title { font-weight: bold; color: #d81b60; margin-bottom: 10px; display: block; border-bottom: 2px solid #ffdee9; padding-bottom: 5px; }
        .order-row { margin: 8px 0; font-size: 14px; color: #444; }
        .tag { font-weight: bold; color: #ad1457; display: inline-block; width: 90px; }
        .guide-item { margin-bottom: 8px; font-size: 13.5px; display: flex; align-items: center; }
        .dot { height: 10px; width: 10px; background-color: #8bc34a; border-radius: 50%; display: inline-block; margin-right: 10px; }
        @media (max-width: 600px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura Tree DB Garden 🌸</h1>
    <div class="controls">
        <input type="number" id="nodeInput" placeholder="เลข">
        <button onclick="addNode()">ปลูกลง DB</button>
        <button onclick="resetTree()" style="background:#90a4ae">ล้างสวน</button>
    </div>
    <canvas id="treeCanvas" width="800" height="400"></canvas>
    <div class="info-grid">
        <div class="result-panel">
            <span class="panel-title">ลำดับการเยือน (Traversal):</span>
            <div class="order-row"><span class="tag">Preorder:</span> <span id="preText">-</span></div>
            <div class="order-row"><span class="tag">Inorder:</span> <span id="inText">-</span></div>
            <div class="order-row"><span class="tag">Postorder:</span> <span id="postText">-</span></div>
        </div>
        <div class="guide-panel">
            <span class="panel-title">สถานะ: ข้อมูลถูกเก็บใน MySQL</span>
            <div class="guide-item"><span class="dot" style="background:#ff69b4"></span> ระบบจะจำค่าไว้แม้ปิด Browser</div>
            <div class="guide-item"><span class="dot" style="background:#ff69b4"></span> เชื่อมต่อผ่าน PDO PHP</div>
        </div>
    </div>
</div>

<script>
    class Node {
        constructor(val) { this.val = val; this.left = null; this.right = null; }
    }

    let root = null;
    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

    // ดึงข้อมูลจากฐานข้อมูลเมื่อโหลดหน้าเว็บ
    window.onload = async function() {
        const response = await fetch('?action=get');
        const nodes = await response.json();
        nodes.forEach(val => buildTreeFromValue(parseInt(val)));
        render();
    };

    async function addNode() {
        const input = document.getElementById('nodeInput');
        const val = parseInt(input.value);
        if (isNaN(val)) return;

        // ส่งค่าไปบันทึกใน PHP (MySQL)
        const formData = new FormData();
        formData.append('val', val);
        const response = await fetch('?action=add', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.status === 'success') {
            buildTreeFromValue(val);
            render();
        } else {
            alert("อาจมีเลขนี้อยู่แล้วในฐานข้อมูล!");
        }
        input.value = '';
    }

    function buildTreeFromValue(val) {
        if (!root) root = new Node(val);
        else insertNode(root, val);
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
        const vSpace = 70;
        if (node.left) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x - space, y + vSpace);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 3; ctx.stroke();
            drawTree(node.left, x - space, y + vSpace, space / 1.9);
        }
        if (node.right) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x + space, y + vSpace);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 3; ctx.stroke();
            drawTree(node.right, x + space, y + vSpace, space / 1.9);
        }
        ctx.beginPath(); ctx.arc(x, y, 20, 0, Math.PI * 2);
        ctx.fillStyle = "white"; ctx.fill(); ctx.strokeStyle = "#ff69b4"; ctx.lineWidth = 2; ctx.stroke();
        ctx.fillStyle = "#ad1457"; ctx.font = "bold 14px Arial"; ctx.textAlign = "center"; ctx.fillText(node.val, x, y + 5);
    }

    async function resetTree() {
        await fetch('?action=clear');
        root = null;
        render();
    }

    function getPreorder(n, r=[]) { if(n){ r.push(n.val); getPreorder(n.left, r); getPreorder(n.right, r); } return r; }
    function getInorder(n, r=[]) { if(n){ getInorder(n.left, r); r.push(n.val); getInorder(n.right, r); } return r; }
    function getPostorder(n, r=[]) { if(n){ getPostorder(n.left, r); getPostorder(n.right, r); r.push(n.val); } return r; }

    function updateTraversalText() {
        document.getElementById('preText').innerText = getPreorder(root).join(' - ') || '-';
        document.getElementById('inText').innerText = getInorder(root).join(' - ') || '-';
        document.getElementById('postText').innerText = getPostorder(root).join(' - ') || '-';
    }
</script>
</body>
</html>

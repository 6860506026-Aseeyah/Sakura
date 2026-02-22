<?php
// 1. เชื่อมต่อฐานข้อมูลโดยดึงค่าจาก Environment Settings
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');

// เชื่อมต่อโดยระบุพอร์ตมาตรฐาน 3306
$conn = new mysqli($host, $user, $pass, $db);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. เมื่อกด "ปลูก Node" (ส่งค่าเข้า MariaDB)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['node_val'])) {
    $val = intval($_POST['node_val']);
    // ใช้ชื่อคอลัมน์ node_value ให้ตรงกับใน Terminal ของคุณ
    $conn->query("INSERT INTO sakura_nodes (node_value) VALUES ('$val')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 3. เมื่อกด "ล้างสวน" (ลบข้อมูลใน MariaDB)
if (isset($_POST['reset_tree'])) {
    $conn->query("TRUNCATE TABLE sakura_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลจาก MariaDB มาเก็บในตัวแปร เพื่อนำไปวาดต้นไม้
$db_nodes = [];
$res = $conn->query("SELECT node_value FROM sakura_nodes ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $db_nodes[] = (int)$row['node_value'];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sakura Binary Tree - MariaDB</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px 0; }
        .container { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); padding: 30px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.3); text-align: center; width: 90%; max-width: 900px; border: 2px solid #fff; }
        h1 { color: #d81b60; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 100px; text-align: center; font-size: 16px; outline: none; }
        button { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; margin-left: 5px; transition: 0.3s; }
        button:hover { background: #e91e63; transform: scale(1.05); }
        .btn-reset { background: #90a4ae; margin-top: 10px; }
        canvas { background: rgba(255, 255, 255, 0.5); border-radius: 20px; border: 2px solid #fff; max-width: 100%; margin-top: 20px; }
        .result-panel { background: white; padding: 20px; border-radius: 20px; border: 1px solid #f8bbd0; margin-top: 25px; text-align: left; }
        .tag { font-weight: bold; color: #ad1457; display: inline-block; width: 100px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura Binary Tree 🌸</h1>
    
    <form method="POST" style="margin-bottom: 10px;">
        <input type="number" name="node_val" placeholder="ใส่ตัวเลข" required>
        <button type="submit">ปลูก Node</button>
    </form>
    
    <form method="POST">
        <button type="submit" name="reset_tree" class="btn-reset">ล้างฐานข้อมูล (Reset)</button>
    </form>

    <canvas id="treeCanvas" width="800" height="450"></canvas>

    <div class="result-panel">
        <div style="font-weight:bold; color:#d81b60; margin-bottom:12px; font-size: 1.1em;">🌳 ลำดับการท่องต้นไม้ (ดึงจาก DB):</div>
        <div><span class="tag">Pre-order:</span> <span id="preText">-</span></div>
        <div><span class="tag">In-order:</span> <span id="inText">-</span></div>
        <div><span class="tag">Post-order:</span> <span id="postText">-</span></div>
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

    // รับข้อมูลจาก PHP ที่ดึงมาจาก MariaDB
    const nodesFromDB = <?php echo json_encode($db_nodes); ?>;
    let root = null;

    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

    // สร้างต้นไม้จากข้อมูลใน DB
    nodesFromDB.forEach(val => {
        if (!root) root = new Node(val);
        else insertNode(root, val);
    });

    function insertNode(node, val) {
        if (val < node.val) {
            if (!node.left) node.left = new Node(val);
            else insertNode(node.left, val);
        } else if (val > node.val) {
            if (!node.right) node.right = new Node(val);
            else insertNode(node.right, val);
        }
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (root) drawNode(root, canvas.width / 2, 60, 180);
        updateTraversalText();
    }

    function drawNode(node, x, y, offset) {
        if (node.left) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x - offset, y + 80);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 3; ctx.stroke();
            drawNode(node.left, x - offset, y + 80, offset / 1.8);
        }
        if (node.right) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x + offset, y + 80);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 3; ctx.stroke();
            drawNode(node.right, x + offset, y + 80, offset / 1.8);
        }
        ctx.beginPath(); ctx.arc(x, y, 22, 0, Math.PI * 2);
        ctx.fillStyle = "white"; ctx.fill();
        ctx.strokeStyle = "#ff69b4"; ctx.lineWidth = 2; ctx.stroke();
        ctx.fillStyle = "#ad1457"; ctx.textAlign = "center"; ctx.font = "bold 16px Arial";
        ctx.fillText(node.val, x, y + 6);
    }

    function getPre(n, r=[]) { if(n){ r.push(n.val); getPre(n.left,r); getPre(n.right,r); } return r; }
    function getIn(n, r=[]) { if(n){ getIn(n.left,r); r.push(n.val); getIn(n.right,r); } return r; }
    function getPost(n, r=[]) { if(n){ getPost(n.left,r); getPost(n.right,r); r.push(n.val); } return r; }

    function updateTraversalText() {
        document.getElementById('preText').innerText = getPre(root).join(' → ') || 'ไม่มีข้อมูล';
        document.getElementById('inText').innerText = getIn(root).join(' → ') || 'ไม่มีข้อมูล';
        document.getElementById('postText').innerText = getPost(root).join(' → ') || 'ไม่มีข้อมูล';
    }

    draw();
</script>
</body>
</html>

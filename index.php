<?php
// 1. เชื่อมต่อฐานข้อมูล (ดึงค่าจาก Environment ที่เรากรอกไว้)
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');

$conn = new mysqli($host, $user, $pass, $db);

// 2. ถ้ากด "ปลูก Node" ให้เซฟลงฐานข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['node_val'])) {
    $val = intval($_POST['node_val']);
    // ใช้ชื่อคอลัมน์ node_value ตามที่โชว์ใน Terminal ของคุณ
    $conn->query("INSERT INTO sakura_nodes (node_value) VALUES ($val)");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 3. ถ้ากด "ล้างสวน" ให้ลบข้อมูลในฐานข้อมูล
if (isset($_POST['reset_tree'])) {
    $conn->query("TRUNCATE TABLE sakura_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลมาแสดงผลบนต้นไม้
$db_nodes = [];
$res = $conn->query("SELECT node_value FROM sakura_nodes ORDER BY id ASC");
if ($res) { while ($row = $res->fetch_assoc()) { $db_nodes[] = (int)$row['node_value']; } }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sakura Binary Tree Garden</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px 0; }
        .container { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); padding: 25px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 90%; max-width: 900px; }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 80px; text-align: center; }
        button { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; margin-left: 5px; }
        canvas { background: rgba(255, 255, 255, 0.4); border-radius: 20px; border: 2px solid #fff; max-width: 100%; margin-top: 15px; }
        .result-panel { background: white; padding: 15px; border-radius: 20px; border: 1px solid #f8bbd0; margin-top: 20px; text-align: left; }
        .tag { font-weight: bold; color: #ad1457; display: inline-block; width: 90px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🌸 Sakura Tree Garden 🌸</h1>
    <form method="POST" style="display:inline;">
        <input type="number" name="node_val" placeholder="เลข" required>
        <button type="submit">ปลูก Node</button>
    </form>
    <form method="POST" style="display:inline;">
        <button type="submit" name="reset_tree" style="background:#90a4ae">ล้างสวน</button>
    </form>
    <br>
    <canvas id="treeCanvas" width="800" height="400"></canvas>
    <div class="result-panel">
        <div style="font-weight:bold; color:#d81b60; margin-bottom:10px;">ลำดับ (ดึงจาก MariaDB):</div>
        <div><span class="tag">Preorder:</span> <span id="preText">-</span></div>
        <div><span class="tag">Inorder:</span> <span id="inText">-</span></div>
        <div><span class="tag">Postorder:</span> <span id="postText">-</span></div>
    </div>
</div>

<script>
    class Node { constructor(val) { this.val = val; this.left = null; this.right = null; } }
    const initialNodes = <?php echo json_encode($db_nodes); ?>;
    let root = null;
    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

    initialNodes.forEach(val => {
        if (!root) root = new Node(val);
        else insertNode(root, val);
    });

    function insertNode(node, val) {
        if (val < node.val) { if (!node.left) node.left = new Node(val); else insertNode(node.left, val); }
        else if (val > node.val) { if (!node.right) node.right = new Node(val); else insertNode(node.right, val); }
    }

    function render() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (root) drawTree(root, canvas.width / 2, 50, 180);
        updateText();
    }

    function drawTree(node, x, y, space) {
        if (node.left) { ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x-space, y+70); ctx.strokeStyle='#ffb6c1'; ctx.lineWidth=3; ctx.stroke(); drawTree(node.left, x-space, y+70, space/1.9); }
        if (node.right) { ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x+space, y+70); ctx.strokeStyle='#ffb6c1'; ctx.lineWidth=3; ctx.stroke(); drawTree(node.right, x+space, y+70, space/1.9); }
        ctx.beginPath(); ctx.arc(x, y, 20, 0, Math.PI*2); ctx.fillStyle="white"; ctx.fill(); ctx.strokeStyle="#ff69b4"; ctx.stroke();
        ctx.fillStyle="#ad1457"; ctx.textAlign="center"; ctx.font="bold 14px Arial"; ctx.fillText(node.val, x, y+5);
    }

    function getPre(n, r=[]) { if(n){ r.push(n.val); getPre(n.left,r); getPre(n.right,r); } return r; }
    function getIn(n, r=[]) { if(n){ getIn(n.left,r); r.push(n.val); getIn(n.right,r); } return r; }
    function getPost(n, r=[]) { if(n){ getPost(n.left,r); getPost(n.right,r); r.push(n.val); } return r; }

    function updateText() {
        document.getElementById('preText').innerText = getPre(root).join(' - ') || '-';
        document.getElementById('inText').innerText = getIn(root).join(' - ') || '-';
        document.getElementById('postText').innerText = getPost(root).join(' - ') || '-';
    }
    render();
</script>
</body>
</html>

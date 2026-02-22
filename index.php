<?php
// 1. เชื่อมต่อฐานข้อมูล (MariaDB บน Dokploy)
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. เมื่อกด "ปลูก Node"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['node_val'])) {
    $val = intval($_POST['node_val']);
    $conn->query("INSERT INTO sakura_nodes (node_value) VALUES ('$val')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 3. เมื่อกด "ล้างสวน" (ถ้ามีเลข=ลบเลขนั้น, ถ้าว่าง=ล้างหมด)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reset'])) {
    $target_val = $_POST['reset_val'];

    if (!empty($target_val)) {
        // กรณีใส่เลข: ลบเฉพาะค่าที่ระบุ 1 แถว
        $val_to_del = intval($target_val);
        $conn->query("DELETE FROM sakura_nodes WHERE node_value = '$val_to_del' LIMIT 1");
    } else {
        // กรณีเว้นว่าง: ล้างทั้งหมด
        $conn->query("DELETE FROM sakura_nodes");
        $conn->query("ALTER TABLE sakura_nodes AUTO_INCREMENT = 1");
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลจากฐานข้อมูลมาวาดต้นไม้
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
    <title>Sakura Tree Garden - MariaDB</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px 0; }
        .container { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); padding: 30px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 90%; max-width: 900px; border: 2px solid #fff; }
        h1 { color: #d81b60; font-size: 32px; margin-bottom: 25px; }
        
        .form-group { display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 80px; text-align: center; font-size: 16px; outline: none; }
        
        .btn { padding: 12px 25px; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-insert { background: #ff69b4; }
        .btn-reset { background: #90a4ae; }
        .btn:hover { transform: scale(1.05); opacity: 0.9; }
        
        canvas { background: white; border-radius: 20px; border: 1px solid #f8bbd0; max-width: 100%; margin-bottom: 20px; }
        
        .bottom-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .panel { background: white; padding: 20px; border-radius: 25px; border: 1px solid #f8bbd0; text-align: left; }
        .panel h3 { color: #d81b60; margin-top: 0; font-size: 18px; border-bottom: 2px solid #fce4ec; padding-bottom: 10px; margin-bottom: 15px; }
        
        /* จัดแถว Output และ Traversal */
        .output-row, .traversal-info { 
            margin: 12px 0; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }
        .label-text { font-weight: bold; color: #ad1457; min-width: 90px; }
        .value-text { color: #880e4f; font-weight: bold; }
        .formula-text { font-size: 14px; color: #ad1457; background: #fff0f5; padding: 2px 10px; border-radius: 10px; }

        .dot { height: 10px; width: 10px; background-color: #ff69b4; border-radius: 50%; display: inline-block; }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura Tree Garden 🌸</h1>
    
    <div class="form-group">
        <form method="POST" style="display: inline-flex; gap: 10px;">
            <input type="number" name="node_val" placeholder="เลข" required>
            <button type="submit" class="btn btn-insert">ปลูก Node</button>
        </form>
        
        <form method="POST" style="display: inline-flex; gap: 10px;">
            <input type="number" name="reset_val" placeholder="เลข">
            <button type="submit" name="action_reset" class="btn btn-reset">ล้างสวน</button>
        </form>
    </div>

    <canvas id="treeCanvas" width="800" height="400"></canvas>

    <div class="bottom-panels">
        <div class="panel">
            <h3>ลำดับที่ได้ (Output):</h3>
            <div class="output-row">
                <span class="label-text">Preorder:</span>
                <span id="preText" class="value-text">-</span>
            </div>
            <div class="output-row">
                <span class="label-text">Inorder:</span>
                <span id="inText" class="value-text">-</span>
            </div>
            <div class="output-row">
                <span class="label-text">Postorder:</span>
                <span id="postText" class="value-text">-</span>
            </div>
        </div>

        <div class="panel">
            <h3>3 วิธีหลักในการ Traversal:</h3>
            <div class="traversal-info">
                <span class="dot"></span>
                <span class="label-text">Preorder:</span>
                <span class="formula-text">Root → Left → Right</span>
            </div>
            <div class="traversal-info">
                <span class="dot"></span>
                <span class="label-text">Inorder:</span>
                <span class="formula-text">Left → Root → Right</span>
            </div>
            <div class="traversal-info">
                <span class="dot"></span>
                <span class="label-text">Postorder:</span>
                <span class="formula-text">Left → Right → Root</span>
            </div>
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

    const nodesFromDB = <?php echo json_encode($db_nodes); ?>;
    let root = null;
    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

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
        if (root) drawNode(root, canvas.width / 2, 50, 160);
        updateText();
    }

    function drawNode(node, x, y, offset) {
        if (node.left) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x - offset, y + 70);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 2; ctx.stroke();
            drawNode(node.left, x - offset, y + 70, offset / 1.8);
        }
        if (node.right) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x + offset, y + 70);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 2; ctx.stroke();
            drawNode(node.right, x + offset, y + 70, offset / 1.8);
        }
        ctx.beginPath(); ctx.arc(x, y, 18, 0, Math.PI * 2);
        ctx.fillStyle = "white"; ctx.fill();
        ctx.strokeStyle = "#ff69b4"; ctx.lineWidth = 2; ctx.stroke();
        ctx.fillStyle = "#ad1457"; ctx.textAlign = "center"; ctx.font = "bold 14px Arial";
        ctx.fillText(node.val, x, y + 5);
    }

    function getPre(n, r=[]) { if(n){ r.push(n.val); getPre(n.left,r); getPre(n.right,r); } return r; }
    function getIn(n, r=[]) { if(n){ getIn(n.left,r); r.push(n.val); getIn(n.right,r); } return r; }
    function getPost(n, r=[]) { if(n){ getPost(n.left,r); getPost(n.right,r); r.push(n.val); } return r; }

    function updateText() {
        document.getElementById('preText').innerText = getPre(root).join(' - ') || '-';
        document.getElementById('inText').innerText = getIn(root).join(' - ') || '-';
        document.getElementById('postText').innerText = getPost(root).join(' - ') || '-';
    }

    draw();
</script>
</body>
</html>


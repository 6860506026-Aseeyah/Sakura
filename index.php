<?php
// 1. เชื่อมต่อฐานข้อมูล
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

// 3. ส่วนแก้ไขการลบ (แยกเงื่อนไขให้เด็ดขาด)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_tree'])) {
    $conn->query("DELETE FROM sakura_nodes");
    $conn->query("ALTER TABLE sakura_nodes AUTO_INCREMENT = 1");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลมาแสดงผล
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
    <title>Sakura Tree Garden</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px 0; }
        .container { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); padding: 30px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 90%; max-width: 900px; border: 2px solid #fff; }
        h1 { color: #d81b60; font-size: 32px; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        
        .form-group { display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 80px; text-align: center; font-size: 16px; outline: none; }
        
        .btn-insert { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-reset { padding: 12px 25px; background: #90a4ae; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        button:hover { transform: scale(1.05); opacity: 0.9; }
        
        canvas { background: white; border-radius: 20px; border: 1px solid #f8bbd0; max-width: 100%; margin-bottom: 20px; }
        
        .bottom-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .panel { background: white; padding: 20px; border-radius: 25px; border: 1px solid #f8bbd0; text-align: left; }
        .panel h3 { color: #d81b60; margin-top: 0; font-size: 18px; border-bottom: 2px solid #fce4ec; padding-bottom: 10px; margin-bottom: 15px; }
        
        .output-row { margin: 12px 0; display: flex; align-items: center; gap: 15px; }
        .output-label { font-weight: bold; color: #ad1457; min-width: 85px; }
        .output-value { color: #880e4f; font-weight: bold; letter-spacing: 1px; }

        .traversal-info { margin-bottom: 15px; }
        .dot { height: 12px; width: 12px; background-color: #f8bbd0; border-radius: 50%; display: inline-block; margin-right: 10px; border: 2px solid #ff69b4; }
        .method-title { font-weight: bold; color: #d81b60; }
        .method-desc { font-size: 13px; color: #880e4f; margin-left: 26px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura Tree Garden 🌸</h1>
    
    <div class="form-group">
        <form method="POST" style="display: inline-flex; gap: 10px;">
            <input type="number" name="node_val" placeholder="เลข" required>
            <button type="submit" class="btn-insert">ปลูก Node</button>
        </form>
        
        <form method="POST" style="display: inline-flex;">
            <button type="submit" name="reset_tree" class="btn-reset">ล้างสวน</button>
        </form>
    </div>

    <canvas id="treeCanvas" width="800" height="400"></canvas>

    <div class="bottom-panels">
        <div class="panel">
            <h3>ลำดับที่ได้ (Output):</h3>
            <div class="output-row">
                <span class="output-label">Preorder:</span>
                <span id="preText" class="output-value">-</span>
            </div>
            <div class="output-row">
                <span class="output-label">Inorder:</span>
                <span id="inText" class="output-value">-</span>
            </div>
            <div class="output-row">
                <span class="output-label">Postorder:</span>
                <span id="postText" class="output-value">-</span>
            </div>
        </div>

        <div class="panel">
            <h3>3 วิธีหลักในการ Traversal:</h3>
            <div class="traversal-info">
                <div><span class="dot"></span><span class="method-title">Preorder:</span></div>
                <div class="method-desc">Root → Left → Right</div>
            </div>
            <div class="traversal-info">
                <div><span class="dot"></span><span class="method-title">Inorder:</span></div>
                <div class="method-desc">Left → Root → Right</div>
            </div>
            <div class="traversal-info">
                <div><span class="dot"></span><span class="method-title">Postorder:</span></div>
                <div class="method-desc">Left → Right → Root</div>
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

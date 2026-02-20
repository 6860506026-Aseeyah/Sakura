<?php
session_start();

// --- ส่วนของ Logic การจัดการ Node ---
class Node {
    public $val;
    public $left;
    public $right;

    public function __construct($val) {
        $this->val = intval($val);
        $this->left = null;
        $this->right = null;
    }
}

function insertNode(&$node, $val) {
    if ($node === null) {
        $node = new Node($val);
    } else {
        if ($val < $node->val) {
            insertNode($node->left, $val);
        } else if ($val > $node->val) {
            insertNode($node->right, $val);
        }
    }
}

// --- การจัดการ Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        unset($_SESSION['root']); // ลบข้อมูลใน Session ทิ้งทั้งหมด
        header("Location: " . $_SERVER['PHP_SELF']); // Redirect เพื่อล้างค่า POST
        exit;
    }

    if (isset($_POST['addNode']) && isset($_POST['nodeVal']) && $_POST['nodeVal'] !== '') {
        if (!isset($_SESSION['root'])) {
            $_SESSION['root'] = null;
        }
        insertNode($_SESSION['root'], intval($_POST['nodeVal']));
        header("Location: " . $_SERVER['PHP_SELF']); // Redirect เพื่อป้องกันการเบิ้ล Node เวลา Refresh
        exit;
    }
}

// --- ฟังก์ชันดึงค่าลำดับต่างๆ ---
function getPreorder($node, &$result = []) {
    if ($node) {
        $result[] = $node->val;
        getPreorder($node->left, $result);
        getPreorder($node->right, $result);
    }
    return $result;
}

function getInorder($node, &$result = []) {
    if ($node) {
        getInorder($node->left, $result);
        $result[] = $node->val;
        getInorder($node->right, $result);
    }
    return $result;
}

function getPostorder($node, &$result = []) {
    if ($node) {
        getPostorder($node->left, $result);
        getPostorder($node->right, $result);
        $result[] = $node->val;
    }
    return $result;
}

$treeDataJSON = json_encode($_SESSION['root'] ?? null);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sakura Binary Tree Garden</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px; }
        .container { z-index: 10; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(15px); padding: 30px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 100%; max-width: 900px; }
        h1 { color: #d81b60; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .controls { margin-bottom: 25px; }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 100px; text-align: center; outline: none; font-size: 16px; transition: 0.3s; }
        input:focus { border-color: #ff69b4; box-shadow: 0 0 8px rgba(255,105,180,0.3); }
        button { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: 0.3s; margin-left: 5px; font-size: 16px; }
        button:hover { background: #e91e63; transform: translateY(-2px); }
        .btn-reset { background: #90a4ae; }
        .btn-reset:hover { background: #607d8b; }
        
        #canvasContainer { overflow-x: auto; margin-bottom: 20px; background: rgba(255, 255, 255, 0.4); border-radius: 20px; border: 2px solid #fff; }
        canvas { display: block; margin: 0 auto; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .result-panel, .guide-panel { background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 25px; text-align: left; border: 1px solid #f8bbd0; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .panel-title { font-weight: bold; color: #d81b60; margin-bottom: 12px; display: block; border-bottom: 2px solid #ffdee9; padding-bottom: 5px; }
        .order-row { margin: 10px 0; font-size: 14px; color: #444; line-height: 1.6; }
        .tag { font-weight: bold; color: #ad1457; display: inline-block; width: 90px; }
        
        .guide-item { margin-bottom: 12px; font-size: 14px; display: flex; align-items: center; color: #ad1457; }
        .dot { height: 12px; width: 12px; background-color: #fce4ec; border-radius: 50%; display: inline-block; margin-right: 12px; border: 2px solid #ff80ab; flex-shrink: 0; }

        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura Tree Garden 🌸</h1>
    
    <div class="controls">
        <form method="post">
            <input type="number" name="nodeVal" placeholder="ใส่เลข" required autofocus>
            <button type="submit" name="addNode">ปลูก Node</button>
            <button type="submit" name="reset" class="btn-reset">ล้างสวน</button>
        </form>
    </div>

    <div id="canvasContainer">
        <canvas id="treeCanvas" width="800" height="400"></canvas>
    </div>

    <div class="info-grid">
        <div class="result-panel">
            <span class="panel-title">ลำดับที่ได้ (Output):</span>
            <div class="order-row">
                <span class="tag">Preorder:</span> 
                <span><?php echo !empty($_SESSION['root']) ? implode(' - ', getPreorder($_SESSION['root'])) : '-'; ?></span>
            </div>
            <div class="order-row">
                <span class="tag">Inorder:</span> 
                <span><?php echo !empty($_SESSION['root']) ? implode(' - ', getInorder($_SESSION['root'])) : '-'; ?></span>
            </div>
            <div class="order-row">
                <span class="tag">Postorder:</span> 
                <span><?php echo !empty($_SESSION['root']) ? implode(' - ', getPostorder($_SESSION['root'])) : '-'; ?></span>
            </div>
        </div>

        <div class="guide-panel">
            <span class="panel-title">ลำดับการเข้าชมสวน:</span>
            <div class="guide-item"><span class="dot"></span> <div><strong>Preorder:</strong> โคนต้น → ซ้าย → ขวา</div></div>
            <div class="guide-item"><span class="dot"></span> <div><strong>Inorder:</strong> ซ้าย → โคนต้น → ขวา</div></div>
            <div class="guide-item"><span class="dot"></span> <div><strong>Postorder:</strong> ซ้าย → ขวา → โคนต้น</div></div>
        </div>
    </div>
</div>

<script>
    const treeData = <?php echo $treeDataJSON; ?>;
    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

    // ฟังก์ชันวาดเส้นเชื่อมและโหนด
    function drawTree(node, x, y, space) {
        if (!node) return;

        // วาดกิ่งก้าน (เส้น) ก่อน เพื่อให้อยู่ด้านหลังวงกลม
        ctx.strokeStyle = '#ffb6c1';
        ctx.lineWidth = 3;

        if (node.left) {
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x - space, y + 70);
            ctx.stroke();
            drawTree(node.left, x - space, y + 70, space / 1.8);
        }
        if (node.right) {
            ctx.beginPath();
            ctx.moveTo(x, y);
            ctx.lineTo(x + space, y + 70);
            ctx.stroke();
            drawTree(node.right, x + space, y + 70, space / 1.8);
        }

        // วาดดอกซากุระ (โหนด)
        ctx.beginPath();
        ctx.arc(x, y, 22, 0, Math.PI * 2);
        ctx.fillStyle = "white";
        ctx.fill();
        ctx.strokeStyle = "#ff69b4";
        ctx.lineWidth = 2;
        ctx.stroke();

        // ใส่ตัวเลข
        ctx.fillStyle = "#ad1457";
        ctx.font = "bold 15px Arial";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText(node.val, x, y);
    }

    // ล้าง Canvas และเริ่มวาดถ้ามีข้อมูล
    function init() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (treeData && treeData.val !== undefined) {
            drawTree(treeData, canvas.width / 2, 50, 200);
        }
    }

    init();
</script>

</body>
</html>



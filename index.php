<?php
session_start();

// โครงสร้าง Node สำหรับ Binary Search Tree
class Node {
    public $val;
    public $left;
    public $right;

    public function __construct($val) {
        $this->val = $val;
        $this->left = null;
        $this->right = null;
    }
}

// ฟังก์ชันสำหรับเพิ่ม Node เข้าไปใน Tree (BST Logic)
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

// ฟังก์ชันล้างข้อมูลในสวน
if (isset($_POST['reset'])) {
    $_SESSION['root'] = null;
}

// ฟังก์ชันเพิ่ม Node จาก Form
if (isset($_POST['addNode']) && !empty($_POST['nodeVal'])) {
    $val = intval($_POST['nodeVal']);
    if (!isset($_SESSION['root'])) {
        $_SESSION['root'] = null;
    }
    insertNode($_SESSION['root'], $val);
}

// ฟังก์ชันการทำ Traversal ด้วย PHP
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

// เตรียมข้อมูลส่งให้ Canvas วาด (แปลงเป็น JSON)
$treeDataJSON = json_encode($_SESSION['root'] ?? null);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sakura Binary Tree Garden (PHP Version)</title>
    <style>
        /* CSS ใช้ตัวเดิมที่คุณออกแบบไว้ */
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px 0; }
        .container { z-index: 10; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); padding: 25px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 90%; max-width: 900px; }
        h1 { color: #d81b60; margin-bottom: 20px; }
        .controls { margin-bottom: 20px; }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 80px; text-align: center; }
        button { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        button:hover { background: #e91e63; transform: scale(1.05); }
        canvas { background: rgba(255, 255, 255, 0.4); border-radius: 20px; border: 2px solid #fff; max-width: 100%; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .result-panel, .guide-panel { background: rgba(255, 255, 255, 0.9); padding: 15px; border-radius: 20px; text-align: left; border: 1px solid #f8bbd0; }
        .panel-title { font-weight: bold; color: #d81b60; margin-bottom: 10px; display: block; border-bottom: 2px solid #ffdee9; }
        .order-row { margin: 8px 0; font-size: 14px; }
        .tag { font-weight: bold; color: #ad1457; display: inline-block; width: 90px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura PHP Garden 🌸</h1>
    
    <div class="controls">
        <form method="post" style="display: inline;">
            <input type="number" name="nodeVal" placeholder="เลข" required autofocus>
            <button type="submit" name="addNode">ปลูก Node</button>
            <button type="submit" name="reset" style="background:#90a4ae">ล้างสวน</button>
        </form>
    </div>

    <canvas id="treeCanvas" width="800" height="400"></canvas>

    <div class="info-grid">
        <div class="result-panel">
            <span class="panel-title">ลำดับที่ได้ (PHP Processed):</span>
            <div class="order-row">
                <span class="tag">Preorder:</span> 
                <span><?php echo implode(' - ', getPreorder($_SESSION['root'] ?? null)) ?: '-'; ?></span>
            </div>
            <div class="order-row">
                <span class="tag">Inorder:</span> 
                <span><?php echo implode(' - ', getInorder($_SESSION['root'] ?? null)) ?: '-'; ?></span>
            </div>
            <div class="order-row">
                <span class="tag">Postorder:</span> 
                <span><?php echo implode(' - ', getPostorder($_SESSION['root'] ?? null)) ?: '-'; ?></span>
            </div>
        </div>

        <div class="guide-panel">
            <span class="panel-title">Logic หลังบ้าน (PHP):</span>
            <p style="font-size: 12px; color: #666;">ข้อมูลถูกเก็บไว้ใน $_SESSION และประมวลผล Traversal ผ่าน Recursive Function ใน PHP</p>
        </div>
    </div>
</div>

<script>
    // ดึงข้อมูล Tree ที่ส่งมาจาก PHP
    const treeData = <?php echo $treeDataJSON; ?>;
    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

    function drawTree(node, x, y, space) {
        if (!node) return;

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

    // วาดรูปจากข้อมูล JSON ที่ PHP ส่งมา
    if (treeData) {
        drawTree(treeData, canvas.width / 2, 50, 180);
    }
</script>

</body>
</html>


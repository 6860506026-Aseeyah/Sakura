<?php
session_start();

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

if (isset($_POST['reset'])) {
    $_SESSION['root'] = null;
}

if (isset($_POST['addNode']) && !empty($_POST['nodeVal'])) {
    $val = intval($_POST['nodeVal']);
    if (!isset($_SESSION['root'])) {
        $_SESSION['root'] = null;
    }
    insertNode($_SESSION['root'], $val);
}

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
    <title>Sakura Binary Tree Garden</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px 0; }
        .container { z-index: 10; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); padding: 25px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 90%; max-width: 900px; }
        h1 { color: #d81b60; margin-bottom: 20px; }
        .controls { margin-bottom: 20px; }
        input { padding: 12px; border: 2px solid #ffb6c1; border-radius: 15px; width: 80px; text-align: center; outline: none; }
        button { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: 0.3s; margin-left: 5px; }
        button:hover { background: #e91e63; transform: scale(1.05); }
        canvas { background: rgba(255, 255, 255, 0.4); border-radius: 20px; border: 2px solid #fff; max-width: 100%; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .result-panel, .guide-panel { background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 25px; text-align: left; border: 1px solid #f8bbd0; }
        .panel-title { font-weight: bold; color: #d81b60; margin-bottom: 12px; display: block; border-bottom: 2px solid #ffdee9; padding-bottom: 5px; }
        .order-row { margin: 8px 0; font-size: 14px; color: #444; }
        .tag { font-weight: bold; color: #ad1457; display: inline-block; width: 90px; }
        
        /* สไตล์สำหรับส่วนคู่มือจากรูปภาพ */
        .guide-item { margin-bottom: 10px; font-size: 14px; display: flex; align-items: center; color: #2e7d32; }
        .dot { height: 12px; width: 12px; background-color: #a5d6a7; border-radius: 50%; display: inline-block; margin-right: 12px; border: 2px solid #66bb6a; }

        @media (max-width: 650px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <h1>🌸 Sakura Tree Garden 🌸</h1>
    
    <div class="controls">
        <form method="post">
            <input type="number" name="nodeVal" placeholder="เลข" required autofocus>
            <button type="submit" name="addNode">ปลูก Node</button>
            <button type="submit" name="reset" style="background:#90a4ae">ล้างสวน</button>
        </form>
    </div>

    <canvas id="treeCanvas" width="800" height="400"></canvas>

    <div class="info-grid">
        <div class="result-panel">
            <span class="panel-title">ลำดับที่ได้ (Output):</span>
            <div class="order-row"><span class="tag">Preorder:</span> <span><?php echo implode(' - ', getPreorder($_SESSION['root'] ?? null)) ?: '-'; ?></span></div>
            <div class="order-row"><span class="tag">Inorder:</span> <span><?php echo implode(' - ', getInorder($_SESSION['root'] ?? null)) ?: '-'; ?></span></div>
            <div class="order-row"><span class="tag">Postorder:</span> <span><?php echo implode(' - ', getPostorder($_SESSION['root'] ?? null)) ?: '-'; ?></span></div>
        </div>

        <div class="guide-panel">
            <span class="panel-title">3 วิธีหลักในการ Traversal:</span>
            <div class="guide-item"><span class="dot"></span> <div><strong>Preorder:</strong><br><small>Root → Left → Right</small></div></div>
            <div class="guide-item"><span class="dot"></span> <div><strong>Inorder:</strong><br><small>Left → Root → Right</small></div></div>
            <div class="guide-item"><span class="dot"></span> <div><strong>Postorder:</strong><br><small>Left → Right → Root</small></div></div>
        </div>
    </div>
</div>

<script>
    const treeData = <?php echo $treeDataJSON; ?>;
    const canvas = document.getElementById('treeCanvas');
    const ctx = canvas.getContext('2d');

    function drawTree(node, x, y, space) {
        if (!node) return;
        if (node.left) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x - space, y + 70);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 3; ctx.stroke();
            drawTree(node.left, x - space, y + 70, space / 1.9);
        }
        if (node.right) {
            ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x + space, y + 70);
            ctx.strokeStyle = '#ffb6c1'; ctx.lineWidth = 3; ctx.stroke();
            drawTree(node.right, x + space, y + 70, space / 1.9);
        }
        ctx.beginPath(); ctx.arc(x, y, 20, 0, Math.PI * 2);
        ctx.fillStyle = "white"; ctx.fill();
        ctx.strokeStyle = "#ff69b4"; ctx.lineWidth = 2; ctx.stroke();
        ctx.fillStyle = "#ad1457"; ctx.font = "bold 14px Arial"; ctx.textAlign = "center";
        ctx.fillText(node.val, x, y + 5);
    }

    if (treeData) drawTree(treeData, canvas.width / 2, 50, 180);
</script>

</body>
</html>



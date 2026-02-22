<?php
// ดึงค่าจาก Environment ที่เรากรอกใน Dokploy
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');

$conn = new mysqli($host, $user, $pass, $db);

// ส่วนของปุ่ม "ปลูก Node"
if (isset($_POST['node_val'])) {
    $val = intval($_POST['node_val']);
    $conn->query("INSERT INTO sakura_nodes (node_value) VALUES ($val)");
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit();
}

// ดึงข้อมูลมาแสดงผล
$res = $conn->query("SELECT node_value FROM sakura_nodes ORDER BY id ASC");
$db_nodes = [];
while($row = $res->fetch_assoc()) { $db_nodes[] = (int)$row['node_value']; }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sakura Binary Tree</title>
    <style>
        /* สไตล์หน้าเว็บแบบซากุระที่คุณชอบ */
        body { font-family: 'Tahoma', sans-serif; background: #fff0f5; text-align: center; }
        .container { background: white; padding: 30px; border-radius: 30px; display: inline-block; margin-top: 50px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-add { background: #ff69b4; color: white; padding: 10px 20px; border: none; border-radius: 10px; cursor: pointer; }
        .traversal-box { margin-top: 20px; text-align: left; background: #fff9fb; padding: 15px; border-radius: 15px; border: 1px solid #ffb6c1; }
        .tree { display: flex; justify-content: center; margin-top: 30px; }
        .tree ul { padding-top: 20px; position: relative; display: flex; }
        .tree li { list-style-type: none; position: relative; padding: 20px 5px 0 5px; }
        .tree li::before, .tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid #ffb6c1; width: 50%; height: 20px; }
        .tree li::after { right: auto; left: 50%; border-left: 2px solid #ffb6c1; }
        .tree span { border: 2px solid #ffb6c1; padding: 8px 12px; border-radius: 50%; display: inline-block; background: white; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌸 Sakura Tree Garden 🌸</h1>
        <form method="POST">
            <input type="number" name="node_val" placeholder="ใส่เลข" required>
            <button type="submit" class="btn-add">ปลูก Node</button>
        </form>
        <div class="traversal-box">
            <p><strong>Preorder:</strong> <span id="pre-out">-</span></p>
            <p><strong>Inorder:</strong> <span id="in-out">-</span></p>
            <p><strong>Postorder:</strong> <span id="post-out">-</span></p>
        </div>
        <div id="tree-display" class="tree"></div>
    </div>

    <script>
        const nodes = <?php echo json_encode($db_nodes); ?>;
        class Node { constructor(v) { this.val = v; this.left = null; this.right = null; } }
        function insert(root, v) {
            if(!root) return new Node(v);
            if(v < root.val) root.left = insert(root.left, v);
            else root.right = insert(root.right, v);
            return root;
        }
        let treeRoot = null;
        nodes.forEach(v => { treeRoot = insert(treeRoot, v); });

        // ส่วนคำนวณ Traversal
        function getPre(n, a){ if(n){ a.push(n.val); getPre(n.left,a); getPre(n.right,a); } }
        function getIn(n, a){ if(n){ getIn(n.left,a); a.push(n.val); getIn(n.right,a); } }
        function getPost(n, a){ if(n){ getPost(n.left,a); getPost(n.right,a); a.push(n.val); } }

        if(treeRoot) {
            let a1=[], a2=[], a3=[];
            getPre(treeRoot, a1); getIn(treeRoot, a2); getPost(treeRoot, a3);
            document.getElementById('pre-out').innerText = a1.join(' - ');
            document.getElementById('in-out').innerText = a2.join(' - ');
            document.getElementById('post-out').innerText = a3.join(' - ');
            document.getElementById('tree-display').innerHTML = `<ul>${render(treeRoot)}</ul>`;
        }
        function render(n) {
            if(!n) return '';
            return `<li><span>${n.val}</span><ul>${render(n.left) || '<li><span style="visibility:hidden"></span></li>'}${render(n.right) || '<li><span style="visibility:hidden"></span></li>'}</ul></li>`;
        }
    </script>
</body>
</html>

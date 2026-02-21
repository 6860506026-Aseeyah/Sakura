<?php
// 1. เชื่อมต่อฐานข้อมูล (ใช้ข้อมูลเดิมที่คุณทำสำเร็จแล้ว)
$conn = new mysqli("s6860506026db-treesdb-guk6gh", "root", "Aseeyahchekamoh", "treedb");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. บันทึกข้อมูลเมื่อมีการกดปุ่ม "ปลูก Node"
if (isset($_POST['node_val']) && $_POST['node_val'] !== "") {
    $val = intval($_POST['node_val']);
    $stmt = $conn->prepare("INSERT INTO sakura_nodes (node_value) VALUES (?)");
    $stmt->bind_param("i", $val);
    $stmt->execute();
    
    // สำคัญมาก: เมื่อบันทึกเสร็จ ให้ Redirect กลับมาที่หน้าเดิม 
    // เพื่อป้องกันไม่ให้ข้อมูลซ้ำเวลาผู้ใช้กด F5 (Refresh)
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit();
}

// 3. ล้างสวน
if (isset($_POST['clear_db'])) {
    $conn->query("TRUNCATE TABLE sakura_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลทั้งหมดจากฐานข้อมูลมาโชว์ (ข้อมูลจะไม่มีทางหายถ้าอยู่ใน DB)
$res = $conn->query("SELECT node_value FROM sakura_nodes ORDER BY id ASC");
$db_nodes = [];
while($row = $res->fetch_assoc()) {
    $db_nodes[] = (int)$row['node_value'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sakura Tree Garden - Persistent</title>
    <style>
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); text-align: center; padding: 20px; min-height: 100vh; }
        .container { background: rgba(255, 255, 255, 0.9); padding: 25px; border-radius: 40px; display: inline-block; min-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        input { padding: 12px; border-radius: 10px; border: 2px solid #ffb6c1; width: 150px; outline: none; }
        button { padding: 12px 25px; border-radius: 10px; border: none; cursor: pointer; color: white; font-weight: bold; transition: 0.3s; }
        .btn-add { background-color: #ff69b4; }
        .btn-clear { background-color: #8fa3ad; margin-left: 10px; }
        
        .traversal-box { margin: 20px auto; padding: 15px; background: #fff; border-radius: 20px; border: 2px solid #ffb6c1; max-width: 600px; }
        .traversal-box p { margin: 8px 0; font-weight: bold; color: #d02090; font-size: 1.1em; }

        .tree { display: flex; justify-content: center; margin-top: 50px; }
        .tree ul { padding-top: 20px; position: relative; display: flex; justify-content: center; }
        .tree li { list-style-type: none; position: relative; padding: 20px 5px 0 5px; }
        .tree li::before, .tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid #ffb6c1; width: 50%; height: 20px; }
        .tree li::after { right: auto; left: 50%; border-left: 2px solid #ffb6c1; }
        .tree li:only-child::after, .tree li:only-child::before { display: none; }
        .tree li:only-child { padding-top: 0; }
        .tree li:first-child::before, .tree li:last-child::after { border: 0 none; }
        .tree li:last-child::before { border-right: 2px solid #ffb6c1; border-radius: 0 5px 0 0; }
        .tree li:first-child::after { border-radius: 5px 0 0 0; }
        .tree ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid #ffb6c1; width: 0; height: 20px; }
        .tree li span { border: 2px solid #ffb6c1; padding: 10px 15px; border-radius: 50%; background: #fff; display: inline-block; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌸 Persistent Sakura Tree 🌸</h1>
        
        <form method="POST">
            <input type="number" name="node_val" placeholder="ใส่ตัวเลข" required>
            <button type="submit" class="btn-add">ปลูก Node</button>
            <button type="submit" name="clear_db" class="btn-clear">ล้างสวน</button>
        </form>

        <div class="traversal-box">
            <p>Preorder: <span id="pre-out"></span></p>
            <p>Inorder: <span id="in-out"></span></p>
            <p>Postorder: <span id="post-out"></span></p>
        </div>

        <div class="tree" id="tree-display"></div>
    </div>

    <script>
        const nodes = <?php echo json_encode($db_nodes); ?>;

        class Node {
            constructor(val) { this.val = val; this.left = null; this.right = null; }
        }

        function insert(root, val) {
            if (!root) return new Node(val);
            if (val < root.val) root.left = insert(root.left, val);
            else root.right = insert(root.right, val);
            return root;
        }

        let treeRoot = null;
        nodes.forEach(v => { treeRoot = insert(treeRoot, v); });

        let pre = [], ino = [], post = [];
        function traverse(n) {
            if(!n) return;
            pre.push(n.val);
            traverse(n.left);
            ino.push(n.val);
            traverse(n.right);
            post.push(n.val); // หมายเหตุ: logic การเก็บค่า postorder จริงๆ ต้องย้ายตำแหน่งตามทฤษฎี แต่ตัวแปรเหล่านี้จะถูกอัปเดตข้างล่างให้เป๊ะครับ
        }

        // ฟังก์ชันช่วยท่องโหนดให้เป๊ะตามทฤษฎี
        function getPre(n, a){ if(n){ a.push(n.val); getPre(n.left, a); getPre(n.right, a); } }
        function getIn(n, a){ if(n){ getIn(n.left, a); a.push(n.val); getIn(n.right, a); } }
        function getPost(n, a){ if(n){ getPost(n.left, a); getPost(n.right, a); a.push(n.val); } }

        if (treeRoot) {
            let pr=[], i=[], po=[];
            getPre(treeRoot, pr); getIn(treeRoot, i); getPost(treeRoot, po);
            document.getElementById('pre-out').innerText = pr.join(' -> ');
            document.getElementById('in-out').innerText = i.join(' -> ');
            document.getElementById('post-out').innerText = po.join(' -> ');
            document.getElementById('tree-display').innerHTML = `<ul>${render(treeRoot)}</ul>`;
        }

        function render(n) {
            if (!n) return '';
            let h = `<li><span>${n.val}</span>`;
            if (n.left || n.right) {
                h += `<ul>${render(n.left) || '<li><span style="visibility:hidden"></span></li>'}${render(n.right) || '<li><span style="visibility:hidden"></span></li>'}</ul>`;
            }
            return h + `</li>`;
        }
    </script>
</body>
</html>

<?php
// 1. เชื่อมต่อฐานข้อมูล
$conn = new mysqli("s6860506026db-treesdb-guk6gh", "root", "Aseeyahchekamoh", "treedb");

// 2. บันทึกข้อมูลเมื่อมีการกดปุ่ม "ปลูก Node"
if (isset($_POST['node_val'])) {
    $val = intval($_POST['node_val']);
    $conn->query("INSERT INTO sakura_nodes (node_value) VALUES ($val)");
    header("Location: " . $_SERVER['PHP_SELF']); // ป้องกันการส่งซ้ำเมื่อ Refresh
    exit();
}

// 3. ล้างสวน (ถ้าต้องการ)
if (isset($_POST['clear_db'])) {
    $conn->query("TRUNCATE TABLE sakura_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลทั้งหมดจากฐานข้อมูลมาเตรียมวาดต้นไม้
$res = $conn->query("SELECT node_value FROM sakura_nodes ORDER BY id ASC");
$db_nodes = [];
while($row = $res->fetch_assoc()) {
    $db_nodes[] = $row['node_value'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sakura Binary Tree Garden</title>
    <style>
        /* ใช้ CSS เดิมของคุณได้เลยครับ ผมปรับแค่ส่วน Form นิดเดียว */
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); text-align: center; padding: 20px; }
        .container { background: rgba(255, 255, 255, 0.8); padding: 25px; border-radius: 40px; display: inline-block; min-width: 600px; }
        input { padding: 10px; border-radius: 10px; border: 1px solid #ffb6c1; }
        button { padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; color: white; margin: 5px; }
        .btn-add { background-color: #ff69b4; }
        .btn-clear { background-color: #8fa3ad; }
        #tree-container { height: 400px; position: relative; margin-top: 20px; border: 1px solid #eee; background: white; border-radius: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌸 Sakura Tree Garden 🌸</h1>
        
        <form method="POST">
            <input type="number" name="node_val" placeholder="เลขที่ต้องการปลูก" required>
            <button type="submit" class="btn-add">ปลูก Node</button>
            <button type="submit" name="clear_db" class="btn-clear">ล้างสวน</button>
        </form>

        <div id="tree-container"></div>
    </div>

    <script>
        // ฟังก์ชันวาดต้นไม้ (จำลองแบบง่ายเพื่อให้เห็นภาพ)
        const treeContainer = document.getElementById('tree-container');
        
        function drawNode(val) {
            const node = document.createElement('div');
            node.innerText = val;
            node.style.display = 'inline-block';
            node.style.margin = '10px';
            node.style.padding = '15px';
            node.style.background = '#ffb6c1';
            node.style.borderRadius = '50%';
            node.style.color = 'white';
            treeContainer.appendChild(node);
        }

        // ดึงข้อมูลจาก PHP ที่ได้จาก Database มาวาด
        const savedNodes = <?php echo json_encode($db_nodes); ?>;
        savedNodes.forEach(val => drawNode(val));
    </script>
</body>
</html>


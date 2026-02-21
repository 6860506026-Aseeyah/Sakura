<?php
// 1. เชื่อมต่อฐานข้อมูล
$conn = new mysqli("s6860506026db-treesdb-guk6gh", "root", "Aseeyahchekamoh", "treedb");

// 2. บันทึกข้อมูลเมื่อมีการกดปุ่ม "ปลูก Node"
if (isset($_POST['node_val'])) {
    $val = intval($_POST['node_val']);
    // แก้ชื่อคอลัมน์เป็น node_value ให้ตรงกับที่เราแก้ใน Terminal เมื่อกี้ครับ
    $conn->query("INSERT INTO sakura_nodes (node_value) VALUES ($val)");
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit();
}

// 3. ล้างสวน
if (isset($_POST['clear_db'])) {
    $conn->query("TRUNCATE TABLE sakura_nodes");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. ดึงข้อมูลทั้งหมดจากฐานข้อมูล
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
        /* CSS เดิมของคุณเป๊ะๆ */
        body { font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); text-align: center; padding: 20px; }
        .container { background: rgba(255, 255, 255, 0.8); padding: 25px; border-radius: 40px; display: inline-block; min-width: 600px; }
        input { padding: 10px; border-radius: 10px; border: 1px solid #ffb6c1; }
        button { padding: 10px 20px; border-radius: 10px; border: none; cursor: pointer; color: white; margin: 5px; }
        .btn-add { background-color: #ff69b4; }
        .btn-clear { background-color: #8fa3ad; }
        #tree-container { min-height: 400px; position: relative; margin-top: 20px; border: 1px solid #eee; background: white; border-radius: 20px; padding: 20px; }
        
        /* สไตล์วงกลมซากุระแบบเดิม */
        .sakura-node {
            display: inline-block;
            margin: 10px;
            padding: 15px;
            background: #ffb6c1;
            border-radius: 50%;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
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

        <div id="tree-container">
            <?php 
            // วนลูปสร้างวงกลมซากุระจาก PHP โดยตรงเลยครับ
            foreach ($db_nodes as $val) {
                echo "<div class='sakura-node'>$val</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>

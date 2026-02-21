<?php
// ข้อมูลเชื่อมต่อจากหน้า Internal Credentials ของคุณ
$host     = 'trees_db'; 
$user     = 'Sakura';   
$database = 'treedb';   
$password = 'Aseeyahchekamoh'; 

$conn = new mysqli($host, $user, $password, $database);

// AI ช่วยสร้าง SQL สำหรับ MariaDB
$conn->query("CREATE TABLE IF NOT EXISTS tree_nodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    node_value INT NOT NULL
)");

// บันทึกข้อมูลเมื่อกดปุ่ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nodeInput'])) {
    $val = intval($_POST['nodeInput']);
    $stmt = $conn->prepare("INSERT INTO tree_nodes (node_value) VALUES (?)");
    $stmt->bind_param("i", $val);
    $stmt->execute();
}

// ดึงข้อมูลมาวาดต้นไม้
$result = $conn->query("SELECT node_value FROM tree_nodes");
$nodesFromDB = [];
while($row = $result->fetch_assoc()) {
    $nodesFromDB[] = $row['node_value'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sakura Tree Garden</title>
    <style>
        /* ใช้ CSS เดิมของคุณ */
        body { margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Tahoma', sans-serif; background: linear-gradient(180deg, #ffdee9 0%, #b5fffc 100%); padding: 20px 0; }
        .container { z-index: 10; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); padding: 25px; border-radius: 40px; box-shadow: 0 15px 35px rgba(255, 105, 180, 0.2); text-align: center; width: 90%; max-width: 900px; }
        button { padding: 12px 25px; background: #ff69b4; color: white; border: none; border-radius: 15px; cursor: pointer; transition: 0.3s; margin-left: 5px; }
        canvas { background: rgba(255, 255, 255, 0.4); border-radius: 20px; border: 2px solid #fff; max-width: 100%; margin-top: 15px;}
    </style>
</head>
<body>
<div class="container">
    <h1>🌸 Sakura Tree Garden (MariaDB) 🌸</h1>
    <form method="POST">
        <input type="number" name="nodeInput" placeholder="เลข" required style="padding:12px; border-radius:15px; border:2px solid #ffb6c1; width:80px;">
        <button type="submit">ปลูก Node</button>
    </form>
    <canvas id="treeCanvas" width="800" height="400"></canvas>
</div>
<script>
    let nodesArray = <?php echo json_encode($nodesFromDB); ?>;
    // ... โค้ดวาดต้นไม้ JavaScript เดิมของคุณ ...
</script>
</body>
</html>

<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';$dbname = 'traffic_monitor_db';
$username = 'root';$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'เชื่อมต่อฐานข้อมูลไม่ได้: ' . $e->getMessage()]);
    exit;
}

$action =$_GET['action'] ?? '';

// 1. ดึงข้อมูลทั้งหมด
if ($action === 'list') {
    $stmt =$pdo->query("SELECT * FROM traffic_checkpoints ORDER BY id DESC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// 2. เพิ่มจุดตรวจ / ด่านใหม่
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {$input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่ได้รับข้อมูล JSON']);
        exit;
    }

    try {
        $stmt =$pdo->prepare("INSERT INTO traffic_checkpoints (road_name, location_detail, lat, lng, status, avg_speed, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$input['road_name'] ?? 'ไม่ระบุเส้นทาง',
            $input['location_detail'] ?? '-',
            floatval($input['lat'] ?? 13.0041),
            floatval($input['lng'] ?? 101.1445),$input['status'] ?? 'green',
            intval($input['avg_speed'] ?? 60),
            !empty($input['note']) ?$input['note'] : '-'
        ]);
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลเรียบร้อย']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. อัปเดตสถานะ
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {$input = json_decode(file_get_contents('php://input'), true);
    $stmt =$pdo->prepare("UPDATE traffic_checkpoints SET status = ?, avg_speed = ?, note = ? WHERE id = ?");
    $stmt->execute([$input['status'],
        intval($input['avg_speed']),$input['note'],
        intval($input['id'])
    ]);
    echo json_encode(['status' => 'success', 'message' => 'อัปเดตสำเร็จ']);
    exit;
}

// 4. ลบข้อมูล
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt =$pdo->prepare("DELETE FROM traffic_checkpoints WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    echo json_encode(['status' => 'success', 'message' => 'ลบสำเร็จ']);
    exit;
}
?>
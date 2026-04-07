<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$roomId = (int)($_GET['room_id'] ?? 0);
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';

if (!$roomId || !$checkIn || !$checkOut) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$available = isRoomAvailable($roomId, $checkIn, $checkOut);

$db = getDB();
$stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    echo json_encode(['error' => 'Room not found']);
    exit;
}

$nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
$total = $room['price'] * $nights;

echo json_encode([
    'available' => $available,
    'room_name' => $room['name'],
    'price_per_night' => $room['price'],
    'nights' => $nights,
    'total_price' => $total,
    'message' => $available ? 'ห้องว่าง' : 'ห้องเต็ม'
]);

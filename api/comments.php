<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

$db = getDB();

switch ($action) {
    case 'like':
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }
        $db->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?")->execute([$id]);
        $row = $db->prepare("SELECT likes FROM comments WHERE id = ?");
        $row->execute([$id]);
        $data = $row->fetch();
        echo json_encode(['likes' => $data['likes']]);
        break;

    case 'list':
        $platform = $_GET['platform'] ?? 'all';
        $where = $platform !== 'all' ? "WHERE is_approved = 1 AND platform = " . $db->quote($platform) : "WHERE is_approved = 1";
        $comments = $db->query("SELECT * FROM comments $where ORDER BY created_at DESC LIMIT 20")->fetchAll();
        echo json_encode($comments);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}

<?php
require_once __DIR__ . '/includes/db.php';
$db = getDB();

// Clear existing gallery entries
$db->exec("DELETE FROM gallery");

// Reset rooms cover images
$roomCovers = [
    1 => 'img_69d22f40a1a0c6.42134346.jpg',
    2 => 'img_69d22f61379f23.42022209.jpg',
    3 => 'img_69d22f77a97dd2.32102244.jpg',
];
foreach ($roomCovers as $id => $file) {
    $db->prepare("UPDATE rooms SET cover_image=? WHERE id=?")->execute([$file, $id]);
}

// Seed environment images
$env = [
    '20260405_162312.jpg','20260405_162400.jpg','20260405_162410.jpg',
    '20260405_162424.jpg','20260405_162432.jpg','20260405_162446.jpg',
    '20260405_162454.jpg','20260405_162459.jpg',
];
$stmt = $db->prepare("INSERT INTO gallery (filename, category, sort_order) VALUES (?, 'environment', ?)");
foreach ($env as $i => $f) $stmt->execute([$f, $i + 1]);

// Seed gallery images
$gal = [
    '20260405_162505.jpg','20260405_162532.jpg','20260405_162536.jpg',
    '20260405_162548.jpg','20260405_162616.jpg','20260405_162635.jpg',
    '20260405_162646.jpg','20260405_162711.jpg','20260405_162717.jpg',
    '20260405_162723.jpg','20260405_162729.jpg','20260405_162746.jpg',
    '20260405_162753.jpg','20260405_162809.jpg','20260405_162827.jpg',
    '20260405_162837.jpg','img_69d2306ec03508.94700382.jpg',
];
$stmt = $db->prepare("INSERT INTO gallery (filename, category, sort_order) VALUES (?, 'gallery', ?)");
foreach ($gal as $i => $f) $stmt->execute([$f, $i + 1]);

echo "✅ Done! Gallery seeded: " . (count($env) + count($gal)) . " images, rooms updated.";

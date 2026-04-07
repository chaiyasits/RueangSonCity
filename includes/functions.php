<?php
function uploadImage($file, $dir = 'gallery', $allowedTypes = ['image/jpeg','image/png','image/webp','image/gif']) {
    $uploadDir = __DIR__ . '/../uploads/' . $dir . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if ($file['error'] !== UPLOAD_ERR_OK) return ['error' => 'Upload failed'];
    if (!in_array($file['type'], $allowedTypes)) return ['error' => 'Invalid file type'];
    if ($file['size'] > 10 * 1024 * 1024) return ['error' => 'File too large (max 10MB)'];

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $dest = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => true, 'filename' => $filename, 'path' => '/uploads/' . $dir . '/' . $filename];
    }
    return ['error' => 'Failed to save file'];
}

function isRoomAvailable($roomId, $checkIn, $checkOut, $excludeBookingId = null) {
    $db = getDB();
    $sql = "SELECT COUNT(*) as c FROM bookings
            WHERE room_id = ? AND status != 'cancelled'
            AND check_in < ? AND check_out > ?";
    $params = [$roomId, $checkOut, $checkIn];
    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }
    $result = $db->prepare($sql);
    $result->execute($params);
    return $result->fetch()['c'] == 0;
}

function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function formatPrice($price) {
    return number_format($price, 0) . ' ฿';
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function starRating($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '★' : '☆';
    }
    return $stars;
}

function platformIcon($platform) {
    $icons = [
        'facebook' => '🔵',
        'google' => '🔴',
        'airbnb' => '🟠',
        'tripadvisor' => '🟢',
        'instagram' => '🟣',
        'website' => '⚪',
    ];
    return $icons[$platform] ?? '⚪';
}

function platformLabel($platform) {
    $labels = [
        'facebook' => 'Facebook',
        'google' => 'Google Reviews',
        'airbnb' => 'Airbnb',
        'tripadvisor' => 'TripAdvisor',
        'instagram' => 'Instagram',
        'website' => 'Website',
    ];
    return $labels[$platform] ?? ucfirst($platform);
}

function timeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' ปีที่แล้ว';
    if ($diff->m > 0) return $diff->m . ' เดือนที่แล้ว';
    if ($diff->d > 0) return $diff->d . ' วันที่แล้ว';
    if ($diff->h > 0) return $diff->h . ' ชั่วโมงที่แล้ว';
    if ($diff->i > 0) return $diff->i . ' นาทีที่แล้ว';
    return 'เมื่อกี้';
}

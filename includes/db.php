<?php
/**
 * Database connection — supports both SQLite (local dev) and MySQL (production)
 * Set environment variable DB_TYPE=mysql to use MySQL,
 * or create config.local.php in project root to set env vars automatically.
 */

// Load local config if it exists (production credentials, not in git)
$_configLocal = __DIR__ . '/../config.local.php';
if (file_exists($_configLocal)) {
    require_once $_configLocal;
}
unset($_configLocal);

function getDB() {
    static $db = null;
    if ($db === null) {
        // Support both PHP constants (shared hosting) and env vars (Docker/CLI)
        $type = defined('DB_TYPE') ? DB_TYPE : (getenv('DB_TYPE') ?: 'sqlite');
        try {
            if ($type === 'mysql') {
                $host   = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
                $name   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'homestay');
                $user   = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
                $pass   = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
                $port   = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');
                $dsn    = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
                $db = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } else {
                $path = __DIR__ . '/../database.sqlite';
                $db   = new PDO('sqlite:' . $path);
                $db->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $db->exec('PRAGMA foreign_keys = ON');
            }
            initDB($db, $type);
        } catch (PDOException $e) {
            die('<h2 style="font-family:sans-serif;color:#c00">Database Error</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
        }
    }
    return $db;
}

function dbType() {
    if (defined('DB_TYPE')) return DB_TYPE;
    return getenv('DB_TYPE') ?: 'sqlite';
}

/** MySQL-compatible auto-increment syntax helper */
function sqlAutoInc() {
    return dbType() === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
}

function initDB($db, $type = 'sqlite') {
    $ai      = ($type === 'mysql') ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $dt      = 'DATETIME DEFAULT CURRENT_TIMESTAMP';
    $charset = ($type === 'mysql') ? ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' : '';

    $tables = "
        CREATE TABLE IF NOT EXISTS rooms (
            id {$ai},
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            capacity INT DEFAULT 2,
            amenities TEXT,
            cover_image VARCHAR(255),
            is_active TINYINT DEFAULT 1,
            created_at {$dt}
        ){$charset};
        CREATE TABLE IF NOT EXISTS bookings (
            id {$ai},
            room_id INT,
            guest_name VARCHAR(255) NOT NULL,
            guest_email VARCHAR(255) NOT NULL,
            guest_phone VARCHAR(50),
            check_in DATE NOT NULL,
            check_out DATE NOT NULL,
            guests INT DEFAULT 1,
            total_price DECIMAL(10,2),
            status VARCHAR(20) DEFAULT 'pending',
            special_requests TEXT,
            created_at {$dt}
        ){$charset};
        CREATE TABLE IF NOT EXISTS comments (
            id {$ai},
            author_name VARCHAR(255) NOT NULL,
            author_avatar VARCHAR(255),
            content TEXT NOT NULL,
            rating INT DEFAULT 5,
            platform VARCHAR(50) DEFAULT 'website',
            likes INT DEFAULT 0,
            images TEXT,
            is_approved TINYINT DEFAULT 1,
            created_at {$dt}
        ){$charset};
        CREATE TABLE IF NOT EXISTS gallery (
            id {$ai},
            filename VARCHAR(255) NOT NULL,
            caption VARCHAR(255),
            category VARCHAR(50) DEFAULT 'gallery',
            sort_order INT DEFAULT 0,
            created_at {$dt}
        ){$charset};
        CREATE TABLE IF NOT EXISTS admin_users (
            id {$ai},
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at {$dt}
        ){$charset};
        CREATE TABLE IF NOT EXISTS settings (
            " . ($type === 'mysql' ? '`key`' : 'key') . " VARCHAR(100) PRIMARY KEY,
            value TEXT
        ){$charset};
    ";

    // MySQL can't run multi-statement CREATE in one exec — split them
    if ($type === 'mysql') {
        foreach (array_filter(array_map('trim', explode(';', $tables))) as $sql) {
            if ($sql) $db->exec($sql);
        }
    } else {
        $db->exec($tables);
    }

    // Seed admin
    $count = $db->query("SELECT COUNT(*) as c FROM admin_users")->fetch();
    if ((int)$count['c'] === 0) {
        $hash = password_hash('admin1234', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)")
           ->execute(['admin', $hash]);
    }

    // Seed settings
    $count = $db->query("SELECT COUNT(*) as c FROM settings")->fetch();
    if ((int)$count['c'] === 0) {
        $settingsKey = $type === 'mysql' ? '`key`' : 'key';
        $defaults = [
            ['homestay_name', 'RueangSonCity'],
            ['tagline',       'ที่พัก Pet Friendly ใจกลางกรุงเทพฯ สัตว์เลี้ยงมาได้เลย!'],
            ['address',       '999/1 ลาดสง แขวงลาดสง เขตบางแค กรุงเทพฯ 10160'],
            ['phone',         '081-234-5678'],
            ['email',         'info@rueangson.com'],
            ['line_id',       '@rueangson'],
            ['facebook',      'https://facebook.com/rueangson'],
            ['instagram',     'https://instagram.com/rueangson'],
            ['checkin_time',  '14:00'],
            ['checkout_time', '12:00'],
            ['hero_image',    ''],
            ['map_lat',       '13.686481'],
            ['map_lng',       '100.392273'],
        ];
        $stmt = $db->prepare("INSERT IGNORE INTO settings ({$settingsKey}, value) VALUES (?, ?)");
        if ($type === 'sqlite') {
            $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
        }
        foreach ($defaults as $d) $stmt->execute($d);
    }

    // Seed rooms
    $count = $db->query("SELECT COUNT(*) as c FROM rooms")->fetch();
    if ((int)$count['c'] === 0) {
        $rooms = [
            ['ห้องมาตรฐาน',  'ห้องพักสะดวกสบาย ตกแต่งโทนธรรมชาติ ใกล้คอกเต่าและสวนหย่อม เหมาะสำหรับนักเดินทางที่ต้องการพักผ่อน', 800,  2, 'เตียงคู่, แอร์, ห้องน้ำในตัว, WiFi, ทีวี'],
            ['ห้อง Pet Suite','ห้องพักสำหรับผู้มาพร้อมสัตว์เลี้ยง มีพื้นที่วิ่งเล่นส่วนตัว ห้องน้ำสัตว์เลี้ยงแยก ที่เบดสัตว์เลี้ยงให้ฟรี', 1200, 2, 'เตียงใหญ่, แอร์, ห้องน้ำในตัว, WiFi, พื้นที่สัตว์เลี้ยง, ระเบียง'],
            ['ห้อง Family',  'ห้องพักขนาดใหญ่สำหรับครอบครัว มีพื้นที่นั่งเล่นแยก เหมาะสำหรับครอบครัวที่มาพร้อมสัตว์เลี้ยงหลายตัว', 2000, 4, '2 ห้องนอน, แอร์, ครัว, WiFi, สวนส่วนตัว, ที่จอดรถ'],
        ];
        $stmt = $db->prepare("INSERT INTO rooms (name, description, price, capacity, amenities) VALUES (?, ?, ?, ?, ?)");
        foreach ($rooms as $r) $stmt->execute($r);
    }

    // Seed comments
    $count = $db->query("SELECT COUNT(*) as c FROM comments")->fetch();
    if ((int)$count['c'] === 0) {
        $comments = [
            ['สมหมาย ใจดี',  'พาหมาและแมวมาด้วย เจ้าของใจดีมากๆ เค้าก็ชอบสัตว์ เต่าที่นี่น่ารักมาก ลูกๆ ชอบเลยค่ะ จะกลับมาอีกแน่นอน', 5, 'facebook'],
            ['Nattapong K.', 'Brought my 2 dogs here, super pet-friendly! The turtle enclosure is amazing, kids loved it. Very clean and cozy place.', 5, 'google'],
            ['พิมพ์ วิลัย',   'แมวมาก็ได้นะ เจ้าของน่ารักมากๆ บ้านสะอาด บรรยากาศร่มรื่น ราคาคุ้มมาก ใกล้กรุงเทพฯ แต่เงียบสงบ ชอบมากๆ', 5, 'airbnb'],
            ['ธนากร มีสุข',  'ทำเลดี ใกล้ทางด่วน พาน้องหมามาด้วย ไม่มีปัญหาเลย เต่าน่ารักมาก เจ้าของใจดีมากๆ แนะนำเลยครับ', 5, 'website'],
            ['Sarah M.',     'Perfect for pet owners! Brought my golden retriever and he had the time of his life. The turtles are adorable too!', 5, 'tripadvisor'],
            ['วรรณา สุขสม',  'บ้านสวยมากค่ะ โทนสีน่ารัก สะอาด เจ้าของดูแลดี มีสัตว์เลี้ยงเยอะ น่ารักหมดเลย จะพาเพื่อนมาอีกครั้งหน้าค่ะ', 4, 'instagram'],
        ];
        $stmt = $db->prepare("INSERT INTO comments (author_name, content, rating, platform) VALUES (?, ?, ?, ?)");
        foreach ($comments as $c) $stmt->execute($c);
    }
}

function getSetting($key, $default = '') {
    $db  = getDB();
    $col = dbType() === 'mysql' ? '`key`' : 'key';
    $stmt = $db->prepare("SELECT value FROM settings WHERE {$col} = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function getAllSettings() {
    $db   = getDB();
    $col  = dbType() === 'mysql' ? '`key`' : 'key';
    $rows = $db->query("SELECT {$col} as k, value FROM settings")->fetchAll();
    $out  = [];
    foreach ($rows as $r) $out[$r['k']] = $r['value'];
    return $out;
}

function createPlaceholderImages() {
    // No-op on production — real images uploaded by admin
}

<?php
require_once __DIR__ . '/db.php';

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isAdminLoggedIn() {
    startSession();
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function adminLogin($username, $password) {
    startSession();
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        return true;
    }
    return false;
}

function adminLogout() {
    startSession();
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

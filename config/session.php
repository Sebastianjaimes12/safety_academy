<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /modulo.php');
        exit;
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Usuario';
}
?>

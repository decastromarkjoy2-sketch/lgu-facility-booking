<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'public';
}

function require_admin() {
    $role = $_SESSION['role'] ?? 'public';
    $allowed_roles = ['admin', 'gso_admin', 'mayor_admin'];

    // Check if user has any of the permitted administrative roles
    if (!in_array($role, $allowed_roles)) {
        if (!headers_sent()) {
            header("Location: index.php?tab=calendar&error=unauthorized");
            exit;
        } else {
            // JavaScript fallback if HTTP headers were already sent to the browser
            echo "<script>window.location.href = 'index.php?tab=calendar&error=unauthorized';</script>";
            exit;
        }
    }
}
?>
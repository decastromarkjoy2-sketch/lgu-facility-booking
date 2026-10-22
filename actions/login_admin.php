<?php
require_once '../config/database.php';
session_start();

// Define distinct passcodes for each office desk
$gso_pass   = 'gso123!';      // Password for General Services Office (Annex Building)
$mayor_pass = 'mayor123!';    // Password for Mayor's Office (Conference Room)
$master_pass = 'tagoGSO2026!'; // Master fallback password (from your original code)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_pass = $_POST['admin_pass'] ?? '';
    $admin_type   = $_POST['admin_type'] ?? 'gso'; // Comes from the desk switcher buttons in the modal

    if ($admin_type === 'mayor' && $entered_pass === $mayor_pass) {
        $_SESSION['role'] = 'mayor_admin';
        $_SESSION['office_name'] = "Mayor's Office";
        header("Location: ../index.php?tab=manage");
        exit;
    } 
    elseif ($admin_type === 'gso' && $entered_pass === $gso_pass) {
        $_SESSION['role'] = 'gso_admin';
        $_SESSION['office_name'] = "General Services Office";
        header("Location: ../index.php?tab=manage");
        exit;
    } 
    elseif ($entered_pass === $master_pass) {
        // Master admin fallback with full access to everything
        $_SESSION['role'] = 'admin';
        $_SESSION['office_name'] = "Master Administrator";
        header("Location: ../index.php?tab=manage");
        exit;
    } 
    else {
        header("Location: ../index.php?tab=calendar&error=wrong_pass");
        exit;
    }
}
?>
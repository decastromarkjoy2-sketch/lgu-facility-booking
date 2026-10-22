<?php
ob_start(); // Buffer output to prevent premature header execution errors
session_start();

require_once 'config/database.php';
require_once 'includes/auth.php';

// Handle quick role switching if role parameter is present in URL
if (isset($_GET['role'])) {
    $_SESSION['role'] = $_GET['role'];
    header("Location: index.php?tab=" . ($_GET['tab'] ?? 'calendar'));
    exit;
}

$tab = $_GET['tab'] ?? 'calendar';

// Enforce strict access guard BEFORE outputting any HTML markup
if (in_array($tab, ['manage', 'audit', 'branding'])) {
    require_admin();
}

// Render HTML layout header
require_once 'includes/header.php';
?>

<div id="view-content-wrapper" class="w-full max-w-full overflow-x-hidden">
    <?php
    switch ($tab) {
        case 'request':
            include 'views/reservation_form.php';
            break;
        case 'calendar':
            include 'views/calendar_view.php';
            break;
        case 'manage':
            include 'views/admin_manage.php';
            break;
        case 'audit':
            include 'views/audit_trail.php';
            break;
        case 'branding':
            include 'views/branding.php';
            break;
        case 'pass':
            include 'views/printable_pass.php';
            break;
        default:
            include 'views/calendar_view.php';
            break;
    }
    ?>
</div>

<?php
require_once 'includes/footer.php';
ob_end_flush();
?>
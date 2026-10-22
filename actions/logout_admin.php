<?php
session_start();
$_SESSION['role'] = 'public';
header("Location: ../index.php?tab=request");
exit;
?>

<?php
session_start();
session_unset();
session_destroy();

// Prevent any caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: ../index.php");
exit();
?>

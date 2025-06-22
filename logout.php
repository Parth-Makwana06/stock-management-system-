<?php
$current_page = basename($_SERVER['PHP_SELF']);
include 'config.php';

session_unset();
session_destroy();

header("Location: index.php");
exit();
?>
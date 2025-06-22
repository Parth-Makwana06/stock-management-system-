<?php
$current_page = basename($_SERVER['PHP_SELF']);
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header("Location: index.php?action=register&error=username_or_email_exists");
        } else {
            header("Location: index.php?action=register&error=registration_failed");
        }
        exit();
    }
}
?>
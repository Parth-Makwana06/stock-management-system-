<?php
$current_page = basename($_SERVER['PHP_SELF']);
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stock_id = $_POST['stock_id'];
$quantity = (int)$_POST['quantity'];

// Validate quantity
if ($quantity <= 0) {
    $_SESSION['error'] = "Invalid quantity";
    header("Location: dashboard.php");
    exit();
}

// Get portfolio item
$portfolio_stmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ? AND stock_id = ?");
$portfolio_stmt->execute([$user_id, $stock_id]);
$portfolio_item = $portfolio_stmt->fetch();

// Check if user has enough shares
if (!$portfolio_item || $portfolio_item['quantity'] < $quantity) {
    $_SESSION['error'] = "Not enough shares to sell";
    header("Location: dashboard.php");
    exit();
}

// Get current stock price
$stock_stmt = $conn->prepare("SELECT current_price FROM stocks WHERE stock_id = ?");
$stock_stmt->execute([$stock_id]);
$stock = $stock_stmt->fetch();

// Calculate total proceeds
$total_proceeds = $stock['current_price'] * $quantity;

// Start transaction
$conn->beginTransaction();

try {
    // Update user balance
    $user_stmt = $conn->prepare("SELECT balance FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
    
    $new_balance = $user['balance'] + $total_proceeds;
    $update_balance = $conn->prepare("UPDATE users SET balance = ? WHERE user_id = ?");
    $update_balance->execute([$new_balance, $user_id]);
    
    // Update or remove portfolio item
    $new_quantity = $portfolio_item['quantity'] - $quantity;
    
    if ($new_quantity > 0) {
        $update_portfolio = $conn->prepare("UPDATE portfolio SET quantity = ? WHERE portfolio_id = ?");
        $update_portfolio->execute([$new_quantity, $portfolio_item['portfolio_id']]);
    } else {
        $delete_portfolio = $conn->prepare("DELETE FROM portfolio WHERE portfolio_id = ?");
        $delete_portfolio->execute([$portfolio_item['portfolio_id']]);
    }
    
    // Record transaction
    $insert_transaction = $conn->prepare("INSERT INTO transactions (user_id, stock_id, type, quantity, price) VALUES (?, ?, 'sell', ?, ?)");
    $insert_transaction->execute([$user_id, $stock_id, $quantity, $stock['current_price']]);
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Successfully sold $quantity shares";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error'] = "Transaction failed: " . $e->getMessage();
}

header("Location: dashboard.php");
exit();
?>
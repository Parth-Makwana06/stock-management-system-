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

// Get stock price and user balance
$stock_stmt = $conn->prepare("SELECT current_price FROM stocks WHERE stock_id = ?");
$stock_stmt->execute([$stock_id]);
$stock = $stock_stmt->fetch();

$user_stmt = $conn->prepare("SELECT balance FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Calculate total cost
$total_cost = $stock['current_price'] * $quantity;

// Check if user has enough balance
if ($user['balance'] < $total_cost) {
    $_SESSION['error'] = "Insufficient balance";
    header("Location: dashboard.php");
    exit();
}

// Start transaction
$conn->beginTransaction();

try {
    // Update user balance
    $new_balance = $user['balance'] - $total_cost;
    $update_balance = $conn->prepare("UPDATE users SET balance = ? WHERE user_id = ?");
    $update_balance->execute([$new_balance, $user_id]);
    
    // Check if stock already exists in portfolio
    $portfolio_stmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ? AND stock_id = ?");
    $portfolio_stmt->execute([$user_id, $stock_id]);
    $portfolio_item = $portfolio_stmt->fetch();
    
    if ($portfolio_item) {
        // Update existing portfolio item
        $new_quantity = $portfolio_item['quantity'] + $quantity;
        $new_avg_price = (($portfolio_item['quantity'] * $portfolio_item['purchase_price']) + ($quantity * $stock['current_price'])) / $new_quantity;
        
        $update_portfolio = $conn->prepare("UPDATE portfolio SET quantity = ?, purchase_price = ? WHERE portfolio_id = ?");
        $update_portfolio->execute([$new_quantity, $new_avg_price, $portfolio_item['portfolio_id']]);
    } else {
        // Add new portfolio item
        $insert_portfolio = $conn->prepare("INSERT INTO portfolio (user_id, stock_id, quantity, purchase_price) VALUES (?, ?, ?, ?)");
        $insert_portfolio->execute([$user_id, $stock_id, $quantity, $stock['current_price']]);
    }
    
    // Record transaction
    $insert_transaction = $conn->prepare("INSERT INTO transactions (user_id, stock_id, type, quantity, price) VALUES (?, ?, 'buy', ?, ?)");
    $insert_transaction->execute([$user_id, $stock_id, $quantity, $stock['current_price']]);
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Successfully purchased $quantity shares";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error'] = "Transaction failed: " . $e->getMessage();
}

header("Location: dashboard.php");
exit();
?>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get transactions with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get transactions with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15; // Items per page
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? "%".$_GET['search']."%" : "%";

// Get total count for pagination
$count_stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM transactions t
    JOIN stocks s ON t.stock_id = s.stock_id
    WHERE t.user_id = ? AND (s.symbol LIKE ? OR s.company_name LIKE ?)
");
$count_stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$count_stmt->bindValue(2, $search);
$count_stmt->bindValue(3, $search);
$count_stmt->execute();
$total = $count_stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Get transactions with proper parameter binding
$transactions_stmt = $conn->prepare("
    SELECT t.*, s.symbol, s.company_name 
    FROM transactions t
    JOIN stocks s ON t.stock_id = s.stock_id
    WHERE t.user_id = ? AND (s.symbol LIKE ? OR s.company_name LIKE ?)
    ORDER BY t.transaction_date DESC
    LIMIT ? OFFSET ?
");

// Bind parameters with explicit types
$transactions_stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$transactions_stmt->bindValue(2, $search);
$transactions_stmt->bindValue(3, $search);
$transactions_stmt->bindValue(4, $limit, PDO::PARAM_INT);
$transactions_stmt->bindValue(5, $offset, PDO::PARAM_INT);
$transactions_stmt->execute();

$transactions = $transactions_stmt->fetchAll();
$transactions = $transactions_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reuse styles from dashboard.php */
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c0392b;
        }
        
        .container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: 250px;
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 20px 0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            padding: 12px 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .sidebar-menu li:hover {
            background-color: var(--light-color);
        }
        
        .sidebar-menu li.active {
            background-color: rgba(30, 60, 114, 0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .sidebar-menu li i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        table tr:hover {
            background-color: #f8f9fa;
        }
        
        .positive {
            color: var(--success-color);
        }
        
        .negative {
            color: var(--danger-color);
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: var(--primary-color);
        }
        
        .pagination a:hover {
            background-color: #f8f9fa;
        }
        
        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .export-btn {
            padding: 10px 15px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .export-btn:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Stock Market</div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
            <span>Balance: $<?php echo number_format($user['balance'], 2); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><i class="fas fa-home"></i> <a href="dashboard.php" style="text-decoration:none;color:inherit;">Dashboard</a></li>
                <li><i class="fas fa-chart-line"></i> <a href="market.php" style="text-decoration:none;color:inherit;">Market</a></li>
                <li><i class="fas fa-wallet"></i> <a href="portfolio.php" style="text-decoration:none;color:inherit;">Portfolio</a></li>
                <li class="active"><i class="fas fa-exchange-alt"></i> Transactions</li>
                <li><i class="fas fa-cog"></i> <a href="settings.php" style="text-decoration:none;color:inherit;">Settings</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transaction History</h3>
                    <a href="export_transactions.php" class="export-btn">Export to CSV</a>
                </div>
                <div class="card-body">
                    <div class="search-container">
                        <form method="get" action="transactions.php" style="display:flex;width:100%;gap:10px;">
                            <input type="text" class="search-input" name="search" placeholder="Search transactions..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="search-btn">Search</button>
                        </form>
                    </div>
                    
                    <?php if (count($transactions) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Symbol</th>
                                    <th>Company</th>
                                    <th>Shares</th>
                                    <th>Price</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><span class="<?php echo ($transaction['type'] == 'buy') ? 'positive' : 'negative'; ?>"><?php echo ucfirst($transaction['type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($transaction['symbol']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['company_name']); ?></td>
                                        <td><?php echo $transaction['quantity']; ?></td>
                                        <td>$<?php echo number_format($transaction['price'], 2); ?></td>
                                        <td>$<?php echo number_format($transaction['quantity'] * $transaction['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>">Previous</a>
                            <?php endif; ?>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            if ($start > 1) echo '<span>...</span>';
                            
                            for ($i = $start; $i <= $end; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>" <?php echo ($i == $page) ? 'class="current"' : ''; ?>>
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; 
                            
                            if ($end < $total_pages) echo '<span>...</span>';
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>">Next</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>No transactions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
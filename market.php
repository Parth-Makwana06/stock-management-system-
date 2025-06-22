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

// Get all stocks with optional search
$search = isset($_GET['search']) ? "%".$_GET['search']."%" : "%";
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'company_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

$valid_sorts = ['symbol', 'company_name', 'current_price'];
$valid_orders = ['asc', 'desc'];

if (!in_array($sort, $valid_sorts)) $sort = 'company_name';
if (!in_array($order, $valid_orders)) $order = 'asc';

$stocks_stmt = $conn->prepare("
    SELECT s.*, 
           (SELECT SUM(quantity) FROM portfolio p WHERE p.stock_id = s.stock_id AND p.user_id = ?) as owned_shares
    FROM stocks s
    WHERE s.symbol LIKE ? OR s.company_name LIKE ?
    ORDER BY $sort $order
");
$stocks_stmt->execute([$_SESSION['user_id'], $search, $search]);
$stocks = $stocks_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Market</title>
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
            cursor: pointer;
        }
        
        table th:hover {
            background-color: #e9ecef;
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
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
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
        
        .sort-icon {
            margin-left: 5px;
        }
        
        .owned-badge {
            background-color: var(--success-color);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
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
                <li class="active"><i class="fas fa-chart-line"></i> Market</li>
                <li><i class="fas fa-wallet"></i> <a href="portfolio.php" style="text-decoration:none;color:inherit;">Portfolio</a></li>
                <li><i class="fas fa-exchange-alt"></i> <a href="transactions.php" style="text-decoration:none;color:inherit;">Transactions</a></li>
                <li><i class="fas fa-cog"></i> <a href="settings.php" style="text-decoration:none;color:inherit;">Settings</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stock Market</h3>
                </div>
                <div class="card-body">
                    <div class="search-container">
                        <form method="get" action="market.php" style="display:flex;width:100%;gap:10px;">
                            <input type="text" class="search-input" name="search" placeholder="Search stocks..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="search-btn">Search</button>
                        </form>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th onclick="sortTable('symbol')">Symbol 
                                    <i class="fas fa-sort sort-icon"></i>
                                </th>
                                <th onclick="sortTable('company_name')">Company 
                                    <i class="fas fa-sort sort-icon"></i>
                                </th>
                                <th onclick="sortTable('current_price')">Price 
                                    <i class="fas fa-sort sort-icon"></i>
                                </th>
                                <th>Your Shares</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocks as $stock): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stock['symbol']); ?></td>
                                    <td><?php echo htmlspecialchars($stock['company_name']); ?></td>
                                    <td>$<?php echo number_format($stock['current_price'], 2); ?></td>
                                    <td>
                                        <?php if ($stock['owned_shares'] > 0): ?>
                                            <span class="owned-badge"><?php echo $stock['owned_shares']; ?> shares</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" onclick="window.location.href='buy_stock.php?stock_id=<?php echo $stock['stock_id']; ?>'">Buy</button>
                                        <?php if ($stock['owned_shares'] > 0): ?>
                                            <button class="btn btn-success" onclick="window.location.href='sell_stock.php?stock_id=<?php echo $stock['stock_id']; ?>'">Sell</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function sortTable(column) {
            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get('sort');
            const currentOrder = url.searchParams.get('order');
            
            let newOrder = 'asc';
            if (currentSort === column && currentOrder === 'asc') {
                newOrder = 'desc';
            }
            
            url.searchParams.set('sort', column);
            url.searchParams.set('order', newOrder);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
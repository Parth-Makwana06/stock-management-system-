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

// Get portfolio data with search
$search = isset($_GET['search']) ? "%".$_GET['search']."%" : "%";

$portfolio_stmt = $conn->prepare("
    SELECT p.*, s.symbol, s.company_name, s.current_price 
    FROM portfolio p
    JOIN stocks s ON p.stock_id = s.stock_id
    WHERE p.user_id = ? AND (s.symbol LIKE ? OR s.company_name LIKE ?)
");
$portfolio_stmt->execute([$_SESSION['user_id'], $search, $search]);
$portfolio = $portfolio_stmt->fetchAll();

// Calculate portfolio stats
$portfolio_value = 0;
$total_investment = 0;
foreach ($portfolio as $item) {
    $current_value = $item['quantity'] * $item['current_price'];
    $investment = $item['quantity'] * $item['purchase_price'];
    $portfolio_value += $current_value;
    $total_investment += $investment;
}
$profit_loss = $portfolio_value - $total_investment;
$profit_loss_percentage = ($total_investment > 0) ? ($profit_loss / $total_investment) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio</title>
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
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: var(--dark-color);
            font-size: 16px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card .profit {
            color: var(--success-color);
        }
        
        .stat-card .loss {
            color: var(--danger-color);
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
        
        .chart-container {
            height: 300px;
            margin-bottom: 30px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li class="active"><i class="fas fa-wallet"></i> Portfolio</li>
                <li><i class="fas fa-exchange-alt"></i> <a href="transactions.php" style="text-decoration:none;color:inherit;">Transactions</a></li>
                <li><i class="fas fa-cog"></i> <a href="settings.php" style="text-decoration:none;color:inherit;">Settings</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Portfolio Value</h3>
                    <div class="value">$<?php echo number_format($portfolio_value + $user['balance'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Investments</h3>
                    <div class="value">$<?php echo number_format($portfolio_value, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Cash Balance</h3>
                    <div class="value">$<?php echo number_format($user['balance'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Profit/Loss</h3>
                    <div class="value <?php echo ($profit_loss >= 0) ? 'profit' : 'loss'; ?>">
                        $<?php echo number_format($profit_loss, 2); ?>
                    </div>
                    <div class="<?php echo ($profit_loss_percentage >= 0) ? 'profit' : 'loss'; ?>">
                        <?php echo number_format($profit_loss_percentage, 2); ?>%
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Investments</h3>
                    <form method="get" action="portfolio.php" style="display:flex;gap:10px;">
                        <input type="text" class="search-input" name="search" placeholder="Search your stocks..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="search-btn">Search</button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (count($portfolio) > 0): ?>
                        <div class="chart-container">
                            <canvas id="portfolioChart"></canvas>
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Symbol</th>
                                    <th>Company</th>
                                    <th>Shares</th>
                                    <th>Avg. Cost</th>
                                    <th>Current Price</th>
                                    <th>Value</th>
                                    <th>P/L</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($portfolio as $item): ?>
                                    <?php
                                    $current_value = $item['quantity'] * $item['current_price'];
                                    $investment = $item['quantity'] * $item['purchase_price'];
                                    $pl = $current_value - $investment;
                                    $pl_percent = ($investment > 0) ? ($pl / $investment) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['symbol']); ?></td>
                                        <td><?php echo htmlspecialchars($item['company_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['purchase_price'], 2); ?></td>
                                        <td>$<?php echo number_format($item['current_price'], 2); ?></td>
                                        <td>$<?php echo number_format($current_value, 2); ?></td>
                                        <td class="<?php echo ($pl >= 0) ? 'positive' : 'negative'; ?>">
                                            $<?php echo number_format($pl, 2); ?> (<?php echo number_format($pl_percent, 2); ?>%)
                                        </td>
                                        <td>
                                            <button class="btn btn-success" onclick="window.location.href='sell_stock.php?stock_id=<?php echo $item['stock_id']; ?>'">Sell</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>You don't have any stocks in your portfolio yet. Visit the <a href="market.php">Market</a> to buy some.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Portfolio pie chart
        const ctx = document.getElementById('portfolioChart').getContext('2d');
        const portfolioData = [
            <?php 
            foreach ($portfolio as $item) {
                $value = $item['quantity'] * $item['current_price'];
                echo "{symbol: '".$item['symbol']."', value: $value},";
            }
            ?>
        ];
        
        const labels = portfolioData.map(item => item.symbol);
        const data = portfolioData.map(item => item.value);
        const backgroundColors = [
            '#1e3c72', '#2a5298', '#4CAF50', '#8BC34A', '#CDDC39',
            '#FFC107', '#FF9800', '#FF5722', '#9C27B0', '#673AB7'
        ];
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
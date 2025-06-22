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

// Get portfolio data
$portfolio_stmt = $conn->prepare("
    SELECT p.*, s.symbol, s.company_name, s.current_price
    FROM portfolio p
    JOIN stocks s ON p.stock_id = s.stock_id
    WHERE p.user_id = ?
");
$portfolio_stmt->execute([$_SESSION['user_id']]);
$portfolio = $portfolio_stmt->fetchAll();

// Calculate portfolio value
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

// Get transaction history
$transactions_stmt = $conn->prepare("
    SELECT t.*, s.symbol, s.company_name 
    FROM transactions t
    JOIN stocks s ON t.stock_id = s.stock_id
    WHERE t.user_id = ?
    ORDER BY t.transaction_date DESC
    LIMIT 10
");
$transactions_stmt->execute([$_SESSION['user_id']]);
$transactions = $transactions_stmt->fetchAll();

// Get all stocks
$stocks_stmt = $conn->prepare("SELECT * FROM stocks ORDER BY company_name");
$stocks_stmt->execute();
$stocks = $stocks_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Market Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            font-size: 18px;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
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
    
    <div class="contrainer">
    <ul class="sidebar-menu">
        <li <?php echo ($current_page == 'dashboard.php') ? 'class="active"' : ''; ?>>
            <i class="fas fa-home"></i> <a href="dashboard.php" style="text-decoration:none;color:inherit;">Dashboard</a>
        </li>
        <li <?php echo ($current_page == 'market.php') ? 'class="active"' : ''; ?>>
            <i class="fas fa-chart-line"></i> <a href="market.php" style="text-decoration:none;color:inherit;">Market</a>
        </li>
        <li <?php echo ($current_page == 'portfolio.php') ? 'class="active"' : ''; ?>>
            <i class="fas fa-wallet"></i> <a href="portfolio.php" style="text-decoration:none;color:inherit;">Portfolio</a>
        </li>
        <li <?php echo ($current_page == 'transactions.php') ? 'class="active"' : ''; ?>>
            <i class="fas fa-exchange-alt"></i> <a href="transactions.php" style="text-decoration:none;color:inherit;">Transactions</a>
        </li>
        <li <?php echo ($current_page == 'settings.php') ? 'class="active"' : ''; ?>>
            <i class="fas fa-cog"></i> <a href="settings.php" style="text-decoration:none;color:inherit;">Settings</a>
        </li>
    </ul>
    </div>
        
        <div class="main-content">
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Portfolio Value</h3>
                    <div class="value">$<?php echo number_format($portfolio_value + $user['balance'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Cash Balance</h3>
                    <div class="value">$<?php echo number_format($user['balance'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Investments</h3>
                    <div class="value">$<?php echo number_format($portfolio_value, 2); ?></div>
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
                    <h3 class="card-title">Your Portfolio</h3>
                    <button class="btn btn-primary" onclick="openBuyModal()">Buy Stocks</button>
                </div>
                <div class="card-body">
                    <?php if (count($portfolio) > 0): ?>
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
                                            <button class="btn btn-success" onclick="openSellModal(<?php echo $item['stock_id']; ?>, '<?php echo htmlspecialchars($item['symbol']); ?>', <?php echo $item['quantity']; ?>, <?php echo $item['current_price']; ?>)">Sell</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>You don't have any stocks in your portfolio yet. Click "Buy Stocks" to get started.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Transactions</h3>
                </div>
                <div class="card-body">
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
                    <?php else: ?>
                        <p>No transactions yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Buy Stock Modal -->
    <div class="modal" id="buyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Buy Stocks</h3>
                <button class="close-btn" onclick="closeModal('buyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="buyForm" action="buy_stock.php" method="post">
                    <div class="form-group">
                        <label for="buyStock">Stock</label>
                        <select id="buyStock" name="stock_id" required>
                            <option value="">Select a stock</option>
                            <?php foreach ($stocks as $stock): ?>
                                <option value="<?php echo $stock['stock_id']; ?>" data-price="<?php echo $stock['current_price']; ?>">
                                    <?php echo htmlspecialchars($stock['symbol']); ?> - <?php echo htmlspecialchars($stock['company_name']); ?> ($<?php echo number_format($stock['current_price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="buyQuantity">Quantity</label>
                        <input type="number" id="buyQuantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Estimated Cost</label>
                        <div id="estimatedCost">$0.00</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit" form="buyForm">Buy</button>
                <button class="btn btn-danger" onclick="closeModal('buyModal')">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Sell Stock Modal -->
    <div class="modal" id="sellModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Sell Stocks</h3>
                <button class="close-btn" onclick="closeModal('sellModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="sellForm" action="sell_stock.php" method="post">
                    <input type="hidden" id="sellStockId" name="stock_id">
                    <div class="form-group">
                        <label for="sellStockSymbol">Stock</label>
                        <input type="text" id="sellStockSymbol" readonly>
                    </div>
                    <div class="form-group">
                        <label for="sellCurrentPrice">Current Price</label>
                        <input type="text" id="sellCurrentPrice" readonly>
                    </div>
                    <div class="form-group">
                        <label for="sellMaxQuantity">Available Shares</label>
                        <input type="text" id="sellMaxQuantity" readonly>
                    </div>
                    <div class="form-group">
                        <label for="sellQuantity">Quantity to Sell</label>
                        <input type="number" id="sellQuantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Estimated Proceeds</label>
                        <div id="estimatedProceeds">$0.00</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" type="submit" form="sellForm">Sell</button>
                <button class="btn btn-danger" onclick="closeModal('sellModal')">Cancel</button>
            </div>
        </div>
    </div>
    
    <script>
        // Buy modal functions
        function openBuyModal() {
            document.getElementById('buyModal').style.display = 'flex';
        }
        
        // Sell modal functions
        function openSellModal(stockId, symbol, maxQuantity, currentPrice) {
            document.getElementById('sellStockId').value = stockId;
            document.getElementById('sellStockSymbol').value = symbol;
            document.getElementById('sellCurrentPrice').value = '$' + currentPrice.toFixed(2);
            document.getElementById('sellMaxQuantity').value = maxQuantity;
            document.getElementById('sellQuantity').max = maxQuantity;
            document.getElementById('sellQuantity').value = 1;
            updateEstimatedProceeds();
            document.getElementById('sellModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Calculate estimated cost when buying
        document.getElementById('buyStock').addEventListener('change', function() {
            updateEstimatedCost();
        });
        
        document.getElementById('buyQuantity').addEventListener('input', function() {
            updateEstimatedCost();
        });
        
        function updateEstimatedCost() {
            const stockSelect = document.getElementById('buyStock');
            const quantity = document.getElementById('buyQuantity').value;
            const selectedOption = stockSelect.options[stockSelect.selectedIndex];
            
            if (selectedOption && selectedOption.value && quantity > 0) {
                const price = parseFloat(selectedOption.getAttribute('data-price'));
                const cost = price * quantity;
                document.getElementById('estimatedCost').textContent = '$' + cost.toFixed(2);
            } else {
                document.getElementById('estimatedCost').textContent = '$0.00';
            }
        }
        
        // Calculate estimated proceeds when selling
        document.getElementById('sellQuantity').addEventListener('input', function() {
            updateEstimatedProceeds();
        });
        
        function updateEstimatedProceeds() {
            const quantity = document.getElementById('sellQuantity').value;
            const maxQuantity = parseInt(document.getElementById('sellMaxQuantity').value);
            const currentPrice = parseFloat(document.getElementById('sellCurrentPrice').value.replace('$', ''));
            
            if (quantity > 0 && quantity <= maxQuantity) {
                const proceeds = currentPrice * quantity;
                document.getElementById('estimatedProceeds').textContent = '$' + proceeds.toFixed(2);
            } else {
                document.getElementById('estimatedProceeds').textContent = '$0.00';
            }
        }
    </script>
</body>
</html>
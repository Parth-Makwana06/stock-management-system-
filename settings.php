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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    // Check if username or email already exists (excluding current user)
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $check_stmt->execute([$username, $email, $_SESSION['user_id']]);
    if ($check_stmt->fetch()) {
        $errors[] = "Username or email already exists";
    }
    
    // Password change logic
    $password_changed = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        } else {
            $password_changed = true;
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }
    
    if (empty($errors)) {
        try {
            if ($password_changed) {
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?");
                $update_stmt->execute([$username, $email, $hashed_password, $_SESSION['user_id']]);
                $_SESSION['success'] = "Profile and password updated successfully!";
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
                $update_stmt->execute([$username, $email, $_SESSION['user_id']]);
                $_SESSION['success'] = "Profile updated successfully!";
            }
            
            // Update session username
            $_SESSION['username'] = $username;
            
            header("Location: settings.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
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
        }
        
        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .error {
            color: var(--danger-color);
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fdecea;
            border-radius: 5px;
        }
        
        .success {
            color: var(--success-color);
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle i {
            position: absolute;
            right: 10px;
            top: 35px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Stock Market</div>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
                <li><i class="fas fa-exchange-alt"></i> <a href="transactions.php" style="text-decoration:none;color:inherit;">Transactions</a></li>
                <li class="active"><i class="fas fa-cog"></i> Settings</li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Settings</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="error">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="settings.php">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="current_password">Current Password (leave blank to keep unchanged)</label>
                            <input type="password" id="current_password" name="current_password">
                            <i class="fas fa-eye" onclick="togglePassword('current_password')"></i>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="new_password">New Password (leave blank to keep unchanged)</label>
                            <input type="password" id="new_password" name="new_password">
                            <i class="fas fa-eye" onclick="togglePassword('new_password')"></i>
                        </div>
                        
                        <div class="form-group password-toggle">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                            <i class="fas fa-eye" onclick="togglePassword('confirm_password')"></i>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Danger Zone</h3>
                </div>
                <div class="card-body">
                    <button class="btn btn-danger" onclick="if(confirm('Are you sure you want to delete your account? This cannot be undone.')) { window.location.href='delete_account.php'; }">
                        Delete Account
                    </button>
                    <p style="margin-top: 10px; color: var(--danger-color);">
                        Warning: This will permanently delete your account and all associated data.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
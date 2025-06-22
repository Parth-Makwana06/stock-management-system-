<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4285f4">
    <title>Stock Market</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://plus.unsplash.com/premium_photo-1664476845274-27c2dabdd7f0?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(8px);
            z-index: -1;
            transform: scale(1.02);
        }

        .container {
            background-color: rgb(149 147 147 / 30%);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(125, 122, 122, 0.11);
        }

        h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 30px;
            color: #abc3ef;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.2);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #5175b5;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2a5298;
        }

        .switch-form {
            margin-top: 20px;
            font-size: 14px;
            color: #b6b1b1;
        }

        .switch-form a {
            color: #b6b1b1;
            text-decoration: none;
            font-weight: 500;
        }

        .switch-form a:hover {
            text-decoration: underline;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .input-icon input {
            padding-left: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Stock Market</h1>
        
        <?php if (isset($_GET['action']) && $_GET['action'] == 'register'): ?>
            <!-- Registration Form -->
            <form action="register.php" method="post">
                <div class="form-group input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Username" required>
                </div>
                <div class="form-group input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Register</button>
                <div class="switch-form">
                    Already have an account? <a href="index.php">Login</a>
                </div>
            </form>
        <?php else: ?>
            <!-- Login Form -->
            <form action="login.php" method="post">
                <div class="form-group input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Username" required>
                </div>
                <div class="form-group input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Login</button>
                <div class="switch-form">
                    Don't have an account? <a href="index.php?action=register">Register</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
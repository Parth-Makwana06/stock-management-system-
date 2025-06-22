CREATE DATABASE stock_market;
USE stock_market;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    balance DECIMAL(15,2) DEFAULT 10000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE stocks (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL UNIQUE,
    company_name VARCHAR(100) NOT NULL,
    current_price DECIMAL(15,2) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE portfolio (
    portfolio_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stock_id INT NOT NULL,
    quantity INT NOT NULL,
    purchase_price DECIMAL(15,2) NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (stock_id) REFERENCES stocks(stock_id)
);

CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stock_id INT NOT NULL,
    type ENUM('buy', 'sell') NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (stock_id) REFERENCES stocks(stock_id)
);


INSERT INTO stocks (symbol, company_name, current_price) VALUES
('AAPL', 'Apple Inc.', 175.25),
('GOOGL', 'Alphabet Inc.', 135.75),
('MSFT', 'Microsoft Corporation', 310.45),
('AMZN', 'Amazon.com Inc.', 150.80),
('TSLA', 'Tesla Inc.', 700.50),
('FB', 'Meta Platforms Inc.', 220.30),
('NVDA', 'NVIDIA Corporation', 250.65),
('PYPL', 'PayPal Holdings Inc.', 95.40),
('NFLX', 'Netflix Inc.', 380.20),
('AMD', 'Advanced Micro Devices Inc.', 110.75);

select*from stocks;
select*from users;
select*from portfolio;
select*from transactions;
-- MySQL database schema for WifiHub voucher system

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Packages table
CREATE TABLE packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price INT NOT NULL, -- in Rupiah
    data_limit VARCHAR(20) DEFAULT 'Unlimited',
    duration_hours INT NOT NULL, -- in hours
    validity_hours INT NOT NULL, -- in hours
    speed VARCHAR(20) DEFAULT 'High Speed',
    priority VARCHAR(50) DEFAULT 'Prioritas Tinggi',
    support_available BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    package_id INT,
    order_status VARCHAR(20) DEFAULT 'pending', -- pending, paid, used, expired
    payment_method VARCHAR(50),
    payment_account VARCHAR(100),
    amount_paid INT,
    voucher_code VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    payment_token VARCHAR(255) NULL, -- for Midtrans integration
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (package_id) REFERENCES packages(package_id)
);

-- Vouchers table (for Mikrotik integration)
CREATE TABLE vouchers (
    voucher_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    voucher_code VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL, -- Mikrotik user
    password VARCHAR(50) NOT NULL, -- Mikrotik password
    profile_name VARCHAR(100) NOT NULL, -- Mikrotik profile
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_used BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- Insert initial packages
INSERT INTO packages (name, price, duration_hours, validity_hours) VALUES
('Paket Basic', 5000, 6, 24),
('Paket Plus', 10000, 12, 24),
('Paket Premium', 20000, 24, 48),
('Paket Ultimate', 30000, 48, 72),
('Paket Elite', 40000, 96, 120),
('Paket Pro', 50000, 144, 168);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_voucher_code ON orders(voucher_code);
CREATE INDEX idx_vouchers_username ON vouchers(username);
CREATE INDEX idx_vouchers_code ON vouchers(voucher_code);
CREATE INDEX idx_vouchers_expires_at ON vouchers(expires_at);
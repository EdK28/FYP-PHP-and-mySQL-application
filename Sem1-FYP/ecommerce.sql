-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
id INT AUTO_INCREMENT PRIMARY KEY,
role_name VARCHAR(50) NOT NULL
);

-- Create users table
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL,
email VARCHAR(100) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
role_id INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Create products table
CREATE TABLE products (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100) NOT NULL,
description TEXT,
price DECIMAL(10,2) NOT NULL,
stock INT DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE orders (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
total_price DECIMAL(10,2) NOT NULL,
status ENUM('pending','completed','cancelled') DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order items tables
CREATE TABLE order_items (
id INT AUTO_INCREMENT PRIMARY KEY,
order_id INT NOT NULL,
product_id INT NOT NULL,
quantity INT NOT NULL,
price DECIMAL(10,2) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (order_id) REFERENCES orders(id),
FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT INTO roles (role_name) VALUES ('admin'), ('customer');

ALTER TABLE products ADD COLUMN image VARCHAR(255) AFTER stock;

INSERT INTO products (name, description, price, stock) VALUES
('Gaming Mouse', 'RGB Gaming Mouse', 299.90, 25),
('Onata V3', 'Mechanical keyboard', 599.99, 15),
('Kraken X2', 'Dolby Atmos Game-Ready Headphones', 499.00, 20),
('Lenovo Legion', '144Hz Full HD gaming monitor', 3899.00, 5),
('NVIDIA RTX 5090', '5th Gen Game-Ready GPU', 4599.90, 40);

UPDATE products SET image = 'game-mouse.jpeg' WHERE id=1;
UPDATE products SET image = 'onatav3.jpg' WHERE id=2;
UPDATE products SET image = 'krakenv2.jpg' WHERE id=3;
UPDATE products SET image = 'l-legion.jpg' WHERE id=4;
UPDATE products SET image = '5090.png' WHERE id=5;

ALTER TABLE orders MODIFY COLUMN status ENUM('pending','completed','cancelled') NOT NULL;
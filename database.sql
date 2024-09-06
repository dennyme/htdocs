-- Create the database
CREATE DATABASE IF NOT EXISTS minecraft_server_calculator;

USE minecraft_server_calculator;

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Create prices table
CREATE TABLE IF NOT EXISTS prices (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      ram_price DECIMAL(10, 2) NOT NULL,
    cpu_price DECIMAL(10, 2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Insert initial admin user (password: admin123)
-- Note: In a real-world scenario, use a securely generated password and change it immediately after first login
INSERT INTO admins (username, password) VALUES ('admin', '$2y$10$92ztjRLI3HJ4yTDYUTg0ROzLy1FGfFbxU1MBVoRr0mGZ7DvLQCZs2');

-- Insert initial prices
INSERT INTO prices (ram_price, cpu_price) VALUES (5.00, 10.00);
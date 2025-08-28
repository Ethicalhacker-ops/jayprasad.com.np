-- Run this SQL to create the necessary tables
CREATE DATABASE mailserver_accounts;
USE mailserver_accounts;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    verification_token VARCHAR(64),
    last_login DATETIME,
    ip_address VARCHAR(45)
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255),
    ip_address VARCHAR(45),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0
);

CREATE TABLE registration_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
);

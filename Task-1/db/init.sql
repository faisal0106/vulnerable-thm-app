-- Database initialization script for the lab
CREATE DATABASE IF NOT EXISTS thm;
USE thm;

-- users table with an admin account and an admin notes field (IDOR teaching point)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50),
  password VARCHAR(50),
  email VARCHAR(100),
  notes TEXT
);

INSERT INTO users (id, username, password, email, notes) VALUES
(1, 'user', 'userpass', 'user@example.com', 'Standard user.'),
(2, 'admin', 'adminpass', 'admin@example.com', 'Admin Notes: THM{IDOR_ACCESS}');

-- simple orders table (used for blind SQLi practice — no direct flag output)
CREATE TABLE IF NOT EXISTS orders (
  id INT,
  status VARCHAR(50)
);

INSERT INTO orders VALUES (1, 'shipped');

-- flags table (store all flags here; PHP will query this table)
CREATE TABLE IF NOT EXISTS flags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50),
  flag VARCHAR(100)
);

INSERT INTO flags (name, flag) VALUES
('login_flag', 'THM{SQLI_LOGIN_BYPASS}'),
('xss_flag',   'THM{XSS_REFLECTED}'),
('order_flag', 'THM{BLIND_SQL_INJECTION}'),
('idor_flag',  'THM{IDOR_ACCESS}'),
('debug_flag', 'THM{DEBUG_EXPOSED}');

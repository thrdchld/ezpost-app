CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS social_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider ENUM('facebook', 'threads') NOT NULL,
    access_token TEXT NOT NULL,
    page_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    platform ENUM('facebook', 'threads') NOT NULL,
    content TEXT,
    status ENUM('scheduled', 'published', 'failed') NOT NULL DEFAULT 'published',
    scheduled_at DATETIME NULL,
    error_log TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS post_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Memasukkan akun Admin default (Password akan otomatis di-enkripsi saat login pertama)
INSERT IGNORE INTO users (email, password_hash) VALUES ('thirdchilddesigner@gmail.com', 'Alliswell95');
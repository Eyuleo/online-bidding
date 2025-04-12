-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    recovery_contact VARCHAR(255),
    recovery_contact_type ENUM('email', 'phone'),
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT true,
    account_restricted_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Auctions table
CREATE TABLE auctions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    status ENUM('draft', 'active', 'ended', 'cancelled') DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Items table (items within an auction)
CREATE TABLE auction_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    auction_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    starting_price DECIMAL(10,2) NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
);

-- Item Images table
CREATE TABLE item_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES auction_items(id) ON DELETE CASCADE
);

-- Bids table
CREATE TABLE bids (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES auction_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bid History table (for tracking bid changes and admin actions)
CREATE TABLE bid_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bid_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    old_amount DECIMAL(10,2),
    new_amount DECIMAL(10,2),
    action_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Security Questions table
CREATE TABLE security_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question TEXT NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Security Answers table
CREATE TABLE user_security_answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    answer VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES security_questions(id) ON DELETE RESTRICT
);

-- Insert default security questions
INSERT INTO security_questions (question) VALUES
    ('What was your childhood nickname?'),
    ('What is the name of your first pet?'),
    ('What was your first car?'),
    ('What is your mother''s maiden name?'),
    ('What city were you born in?'),
    ('What is your favorite book?'),
    ('What was the name of your elementary school?'),
    ('What is your favorite movie?');

-- Indexes for better performance
CREATE INDEX idx_auctions_status ON auctions(status);
CREATE INDEX idx_auctions_dates ON auctions(start_date, end_date);
CREATE INDEX idx_items_auction ON auction_items(auction_id);
CREATE INDEX idx_bids_item ON bids(item_id);
CREATE INDEX idx_bids_user ON bids(user_id);
CREATE INDEX idx_bids_status ON bids(status);

-- Triggers to update current_price in auction_items
DELIMITER //

CREATE TRIGGER after_bid_insert
AFTER INSERT ON bids
FOR EACH ROW
BEGIN
    UPDATE auction_items 
    SET current_price = (
        SELECT MAX(amount)
        FROM bids
        WHERE item_id = NEW.item_id
    )
    WHERE id = NEW.item_id;
END//

CREATE TRIGGER after_bid_update
AFTER UPDATE ON bids
FOR EACH ROW
BEGIN
    UPDATE auction_items 
    SET current_price = (
        SELECT MAX(amount)
        FROM bids
        WHERE item_id = NEW.item_id
    )
    WHERE id = NEW.item_id;
END//

DELIMITER ;

-- Recovery OTPs table
CREATE TABLE recovery_otps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- First, add the new columns
ALTER TABLE users
ADD COLUMN recovery_contact VARCHAR(255),
ADD COLUMN recovery_contact_type ENUM('email', 'phone');

-- Migrate existing data
UPDATE users
SET recovery_contact = recovery_email,
    recovery_contact_type = 'email'
WHERE recovery_email IS NOT NULL;

UPDATE users
SET recovery_contact = recovery_phone,
    recovery_contact_type = 'phone'
WHERE recovery_phone IS NOT NULL AND recovery_email IS NULL;

-- Drop the old columns
ALTER TABLE users
DROP COLUMN recovery_email,
DROP COLUMN recovery_phone; 


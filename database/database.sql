-- =====================================================================
-- Epsilon - Second-Hand Book/Gadget Exchange for Students
-- Complete Database Script
--
-- HOW TO USE:
--   Import this single file into phpMyAdmin (or run via mysql CLI).
--   It creates the database, all tables, constraints, indexes,
--   views, stored procedures, triggers, and starter seed data.
--   No manual SQL editing is required.
-- =====================================================================

DROP DATABASE IF EXISTS epsilon_db;
CREATE DATABASE epsilon_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE epsilon_db;


-- =====================================================================
-- SECTION 1: TABLES
-- =====================================================================

-- ---------------------------------------------------------------------
-- Table: User
-- Every registered student is both a potential buyer and seller.
-- ---------------------------------------------------------------------
CREATE TABLE User (
    user_id      INT AUTO_INCREMENT PRIMARY KEY,
    student_id   VARCHAR(20)  NOT NULL UNIQUE,
    full_name    VARCHAR(100) NOT NULL,
    department   VARCHAR(100) NOT NULL,
    batch        VARCHAR(20)  NOT NULL,
    email        VARCHAR(100) NOT NULL UNIQUE,
    phone        VARCHAR(20)  NOT NULL,
    password     VARCHAR(255) NOT NULL,               -- stored as a bcrypt hash
    role         ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    status       ENUM('active', 'banned') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Category
-- Fixed set of product categories.
-- ---------------------------------------------------------------------
CREATE TABLE Category (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Product
-- A single listing posted by a student (the seller).
-- ---------------------------------------------------------------------
CREATE TABLE Product (
    product_id   INT AUTO_INCREMENT PRIMARY KEY,
    seller_id    INT NOT NULL,
    category_id  INT NOT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT NOT NULL,
    price        DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    `condition`  ENUM('New', 'Like New', 'Good', 'Fair', 'Poor') NOT NULL,
    status       ENUM('Available', 'Pending', 'Sold') NOT NULL DEFAULT 'Available',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_seller   FOREIGN KEY (seller_id)   REFERENCES User(user_id)     ON DELETE CASCADE,
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES Category(category_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: ProductImage
-- A product can have multiple images (one-to-many).
-- ---------------------------------------------------------------------
CREATE TABLE ProductImage (
    image_id     INT AUTO_INCREMENT PRIMARY KEY,
    product_id   INT NOT NULL,
    image_path   VARCHAR(255) NOT NULL,
    uploaded_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_image_product FOREIGN KEY (product_id) REFERENCES Product(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Bid
-- Buyers place bids on a product instead of chatting with the seller.
-- ---------------------------------------------------------------------
CREATE TABLE Bid (
    bid_id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id     INT NOT NULL,
    buyer_id       INT NOT NULL,
    bid_amount     DECIMAL(10,2) NOT NULL CHECK (bid_amount > 0),
    -- Filled in when the seller answers with a different price. The agreed
    -- price is therefore COALESCE(counter_amount, bid_amount).
    counter_amount DECIMAL(10,2) NULL,
    status         ENUM('Pending', 'Countered', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bid_product FOREIGN KEY (product_id) REFERENCES Product(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_bid_buyer   FOREIGN KEY (buyer_id)   REFERENCES User(user_id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Transaction
-- Created automatically the moment a seller accepts a bid.
-- ---------------------------------------------------------------------
CREATE TABLE Transaction (
    transaction_id   INT AUTO_INCREMENT PRIMARY KEY,
    product_id       INT NOT NULL,
    bid_id           INT NOT NULL UNIQUE,
    buyer_id         INT NOT NULL,
    seller_id        INT NOT NULL,
    transaction_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status           ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_txn_product FOREIGN KEY (product_id) REFERENCES Product(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_txn_bid     FOREIGN KEY (bid_id)     REFERENCES Bid(bid_id)         ON DELETE CASCADE,
    CONSTRAINT fk_txn_buyer   FOREIGN KEY (buyer_id)   REFERENCES User(user_id)       ON DELETE CASCADE,
    CONSTRAINT fk_txn_seller  FOREIGN KEY (seller_id)  REFERENCES User(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Payment
-- One payment per transaction.
-- ---------------------------------------------------------------------
CREATE TABLE Payment (
    payment_id       INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id   INT NOT NULL UNIQUE,
    payment_method   ENUM('Cash on Delivery', 'bKash', 'Nagad', 'Rocket', 'Bank Transfer') NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    payment_status   ENUM('Pending', 'Paid', 'Failed') NOT NULL DEFAULT 'Pending',
    paid_at          TIMESTAMP NULL DEFAULT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_txn FOREIGN KEY (transaction_id) REFERENCES Transaction(transaction_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: DeliveryAddress
-- Address the buyer wants the product shipped to, per transaction.
-- ---------------------------------------------------------------------
CREATE TABLE DeliveryAddress (
    address_id      INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT NOT NULL UNIQUE,
    receiver_name   VARCHAR(100) NOT NULL,
    phone           VARCHAR(20)  NOT NULL,
    district        VARCHAR(100) NOT NULL,
    area            VARCHAR(100) NOT NULL,
    full_address    TEXT NOT NULL,
    CONSTRAINT fk_address_txn FOREIGN KEY (transaction_id) REFERENCES Transaction(transaction_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Delivery
-- Shipment tracking for a transaction.
-- ---------------------------------------------------------------------
CREATE TABLE Delivery (
    delivery_id       INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id    INT NOT NULL UNIQUE,
    tracking_number   VARCHAR(50) NULL UNIQUE,
    -- Where the buyer collects the item; the seller fills this in after the order
    pickup_address    VARCHAR(255) NULL,
    delivery_status   ENUM('Pending', 'Packed', 'Shipped', 'Delivered') NOT NULL DEFAULT 'Pending',
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_delivery_txn FOREIGN KEY (transaction_id) REFERENCES Transaction(transaction_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Wishlist
-- A user can save a product for later (many-to-many User <-> Product).
-- ---------------------------------------------------------------------
CREATE TABLE Wishlist (
    wishlist_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    product_id   INT NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wishlist_user    FOREIGN KEY (user_id)    REFERENCES User(user_id)       ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES Product(product_id) ON DELETE CASCADE,
    CONSTRAINT uq_wishlist UNIQUE (user_id, product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Review
-- Buyer rates and reviews the seller after delivery.
-- ---------------------------------------------------------------------
CREATE TABLE Review (
    review_id       INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT NOT NULL UNIQUE,
    buyer_id        INT NOT NULL,
    seller_id       INT NOT NULL,
    rating          TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment         TEXT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_txn    FOREIGN KEY (transaction_id) REFERENCES Transaction(transaction_id) ON DELETE CASCADE,
    CONSTRAINT fk_review_buyer  FOREIGN KEY (buyer_id)       REFERENCES User(user_id)  ON DELETE CASCADE,
    CONSTRAINT fk_review_seller FOREIGN KEY (seller_id)      REFERENCES User(user_id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Report
-- A user flags a suspicious product for admin moderation.
-- ---------------------------------------------------------------------
CREATE TABLE Report (
    report_id     INT AUTO_INCREMENT PRIMARY KEY,
    product_id    INT NOT NULL,
    reported_by   INT NOT NULL,
    reason        ENUM('Fake Product', 'Spam', 'Scam', 'Wrong Information', 'Other') NOT NULL,
    description   TEXT NULL,
    status        ENUM('Pending', 'Reviewed', 'Resolved', 'Dismissed') NOT NULL DEFAULT 'Pending',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_report_product FOREIGN KEY (product_id)  REFERENCES Product(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_report_user    FOREIGN KEY (reported_by) REFERENCES User(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Notification
-- In-app notifications for bidding, payment, and delivery events.
-- ---------------------------------------------------------------------
CREATE TABLE Notification (
    notification_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    type              ENUM('New Bid', 'Counter Offer', 'Bid Accepted', 'Bid Rejected',
                           'Payment Successful', 'Delivery Update') NOT NULL,
    -- What the notification is about, so clicking it can open the right page.
    -- Both are nullable because a notification points at one or the other.
    product_id        INT NULL,
    transaction_id    INT NULL,
    message           VARCHAR(255) NOT NULL,
    is_read           TINYINT(1) NOT NULL DEFAULT 0,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user    FOREIGN KEY (user_id)        REFERENCES User(user_id)               ON DELETE CASCADE,
    -- SET NULL keeps the message readable even if the product is removed
    CONSTRAINT fk_notification_product FOREIGN KEY (product_id)     REFERENCES Product(product_id)         ON DELETE SET NULL,
    CONSTRAINT fk_notification_txn     FOREIGN KEY (transaction_id) REFERENCES Transaction(transaction_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: Invoice
-- Auto-generated once a payment is confirmed as Paid.
-- ---------------------------------------------------------------------
CREATE TABLE Invoice (
    invoice_id      INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(30) NOT NULL UNIQUE,
    transaction_id  INT NOT NULL UNIQUE,
    total_amount    DECIMAL(10,2) NOT NULL,
    generated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_txn FOREIGN KEY (transaction_id) REFERENCES Transaction(transaction_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =====================================================================
-- SECTION 2: ALTER TABLE - performance indexes on frequently
-- filtered / joined columns
-- =====================================================================
ALTER TABLE Product      ADD INDEX idx_product_status   (status);
ALTER TABLE Product      ADD INDEX idx_product_category (category_id);
ALTER TABLE Product      ADD INDEX idx_product_seller   (seller_id);
ALTER TABLE Product      ADD INDEX idx_product_price    (price);
ALTER TABLE Bid          ADD INDEX idx_bid_product      (product_id);
ALTER TABLE Bid          ADD INDEX idx_bid_buyer        (buyer_id);
ALTER TABLE Notification ADD INDEX idx_notification_user (user_id, is_read);
ALTER TABLE Report       ADD INDEX idx_report_status    (status);


-- =====================================================================
-- SECTION 3: SEED DATA
-- =====================================================================

-- The categories a student can choose from when posting a product.
-- They can also add their own while posting; these are just the starting set.
INSERT INTO Category (category_name) VALUES
    ('Books'), ('Laptop'), ('Phone'), ('Calculator'), ('Accessories'), ('Others');

/*
 * The only account created by this file: the administrator.
 *
 * Log in with the Student ID 242-15-782 (or the email) and the password
 * kamrul@1. Everything else - students, products, bids - is created by
 * using the site.
 */
INSERT INTO User (student_id, full_name, department, batch, email, phone, password, role) VALUES
    ('242-15-782', 'System Admin', 'Computer Science and Engineering (CSE)', '2024',
     'admin@epsilon.edu', '01700000000',
     '$2y$10$73DKCCCUbyUOIdcDrFt5xeGnLIVf3LlzfiHLg5nMPQjX8kgGBpzGW', 'admin');


-- =====================================================================
-- SECTION 4: VIEWS
-- =====================================================================

-- Combines a product with its category, seller, primary image,
-- seller rating and bid count - powers the home feed and search page.
CREATE VIEW vw_product_feed AS
SELECT
    p.product_id,
    p.title,
    p.description,
    p.price,
    p.`condition`,
    p.status,
    p.created_at,
    c.category_id,
    c.category_name,
    u.user_id  AS seller_id,
    u.full_name AS seller_name,
    (SELECT pi.image_path FROM ProductImage pi
      WHERE pi.product_id = p.product_id ORDER BY pi.image_id ASC LIMIT 1) AS primary_image,
    (SELECT ROUND(AVG(r.rating), 1) FROM Review r WHERE r.seller_id = u.user_id) AS seller_rating,
    (SELECT COUNT(*) FROM Bid b WHERE b.product_id = p.product_id) AS bid_count
FROM Product p
INNER JOIN Category c ON p.category_id = c.category_id
INNER JOIN User u     ON p.seller_id   = u.user_id;

-- Average rating and review count per seller
CREATE VIEW vw_seller_rating AS
SELECT
    seller_id,
    ROUND(AVG(rating), 1) AS avg_rating,
    COUNT(*)              AS total_reviews
FROM Review
GROUP BY seller_id;



-- =====================================================================
-- SECTION 5: STORED PROCEDURES
-- =====================================================================
DELIMITER $$

-- ---------------------------------------------------------------------
-- sp_place_bid
-- Validates and inserts a new bid on a product.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_place_bid (
    IN p_product_id INT,
    IN p_buyer_id   INT,
    IN p_amount     DECIMAL(10,2)
)
BEGIN
    DECLARE v_seller_id INT;
    DECLARE v_status    VARCHAR(20);

    SELECT seller_id, status INTO v_seller_id, v_status
    FROM Product WHERE product_id = p_product_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product not found.';
    ELSEIF v_status <> 'Available' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'This product is no longer available for bidding.';
    ELSEIF v_seller_id = p_buyer_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You cannot bid on your own product.';
    ELSE
        INSERT INTO Bid (product_id, buyer_id, bid_amount, status)
        VALUES (p_product_id, p_buyer_id, p_amount, 'Pending');
    END IF;
END$$

-- ---------------------------------------------------------------------
-- sp_counter_bid
-- The seller answers a bid with a different price instead of simply
-- accepting or rejecting it. The bid then waits for the buyer to decide.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_counter_bid (
    IN p_bid_id INT,
    IN p_amount DECIMAL(10,2)
)
BEGIN
    DECLARE v_status VARCHAR(20);

    SELECT status INTO v_status FROM Bid WHERE bid_id = p_bid_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bid not found.';
    ELSEIF v_status <> 'Pending' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only a pending bid can be countered.';
    ELSEIF p_amount <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The counter amount must be greater than zero.';
    ELSE
        UPDATE Bid
        SET counter_amount = p_amount,
            status         = 'Countered'
        WHERE bid_id = p_bid_id;
    END IF;
END$$

-- ---------------------------------------------------------------------
-- sp_accept_bid
-- Used by the seller to take the original bid, and by the buyer to take
-- the seller's counter offer. It rejects all the other open bids on the
-- same product, marks the product Pending and creates the Transaction,
-- all inside one database transaction that rolls back on any error.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_accept_bid (
    IN p_bid_id INT
)
BEGIN
    DECLARE v_product_id INT;
    DECLARE v_buyer_id   INT;
    DECLARE v_seller_id  INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT product_id, buyer_id INTO v_product_id, v_buyer_id
    FROM Bid
    WHERE bid_id = p_bid_id AND status IN ('Pending', 'Countered')
    FOR UPDATE;

    IF v_product_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bid not found or already processed.';
    END IF;

    SELECT seller_id INTO v_seller_id FROM Product WHERE product_id = v_product_id;

    -- The Transaction is created FIRST on purpose: the trigger on the Bid
    -- update below looks it up so the buyer's notification can link straight
    -- to the payment page.
    INSERT INTO Transaction (product_id, bid_id, buyer_id, seller_id, status)
    VALUES (v_product_id, p_bid_id, v_buyer_id, v_seller_id, 'Pending');

    UPDATE Bid SET status = 'Accepted' WHERE bid_id = p_bid_id;

    UPDATE Bid SET status = 'Rejected'
    WHERE product_id = v_product_id
      AND bid_id <> p_bid_id
      AND status IN ('Pending', 'Countered');

    UPDATE Product SET status = 'Pending' WHERE product_id = v_product_id;

    COMMIT;
END$$

-- ---------------------------------------------------------------------
-- sp_reject_bid
-- Used by the seller to turn a bid down, and by the buyer to turn a
-- counter offer down.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_reject_bid (
    IN p_bid_id INT
)
BEGIN
    UPDATE Bid SET status = 'Rejected'
    WHERE bid_id = p_bid_id AND status IN ('Pending', 'Countered');
END$$

DELIMITER ;


-- =====================================================================
-- SECTION 6: TRIGGERS
-- Every notification is created here rather than in PHP, so it can never
-- be missed. Each one also records the product or transaction it refers
-- to, which is what makes the notification clickable in the app.
-- =====================================================================
DELIMITER $$

-- Notify the seller whenever a new bid comes in
CREATE TRIGGER trg_bid_after_insert
AFTER INSERT ON Bid
FOR EACH ROW
BEGIN
    DECLARE v_seller_id INT;
    DECLARE v_title     VARCHAR(150);

    SELECT seller_id, title INTO v_seller_id, v_title
    FROM Product WHERE product_id = NEW.product_id;

    INSERT INTO Notification (user_id, type, product_id, message)
    VALUES (v_seller_id, 'New Bid', NEW.product_id,
            CONCAT('New bid of Tk ', NEW.bid_amount, ' received on "', v_title, '"'));
END$$

-- Notify the right person whenever a bid changes state
CREATE TRIGGER trg_bid_after_update
AFTER UPDATE ON Bid
FOR EACH ROW
BEGIN
    DECLARE v_title     VARCHAR(150);
    DECLARE v_seller_id INT;
    DECLARE v_txn_id    INT;

    IF NEW.status <> OLD.status THEN
        SELECT title, seller_id INTO v_title, v_seller_id
        FROM Product WHERE product_id = NEW.product_id;

        IF NEW.status = 'Countered' THEN
            INSERT INTO Notification (user_id, type, product_id, message)
            VALUES (NEW.buyer_id, 'Counter Offer', NEW.product_id,
                    CONCAT('The seller countered your bid on "', v_title,
                           '" with Tk ', NEW.counter_amount));

        ELSEIF NEW.status = 'Accepted' THEN
            -- sp_accept_bid has already created the Transaction, so the
            -- notification can take the buyer straight to the payment page
            SELECT transaction_id INTO v_txn_id FROM Transaction WHERE bid_id = NEW.bid_id;

            INSERT INTO Notification (user_id, type, product_id, transaction_id, message)
            VALUES (NEW.buyer_id, 'Bid Accepted', NEW.product_id, v_txn_id,
                    CONCAT('Your bid on "', v_title, '" was accepted. You can pay now.'));

            -- the buyer took the counter offer, so the seller needs telling too
            IF OLD.status = 'Countered' THEN
                INSERT INTO Notification (user_id, type, product_id, transaction_id, message)
                VALUES (v_seller_id, 'Bid Accepted', NEW.product_id, v_txn_id,
                        CONCAT('Your counter offer on "', v_title, '" was accepted.'));
            END IF;

        ELSEIF NEW.status = 'Rejected' THEN
            INSERT INTO Notification (user_id, type, product_id, message)
            VALUES (NEW.buyer_id, 'Bid Rejected', NEW.product_id,
                    CONCAT('Your bid on "', v_title, '" was rejected.'));

            IF OLD.status = 'Countered' THEN
                INSERT INTO Notification (user_id, type, product_id, message)
                VALUES (v_seller_id, 'Bid Rejected', NEW.product_id,
                        CONCAT('Your counter offer on "', v_title, '" was turned down.'));
            END IF;
        END IF;
    END IF;
END$$

-- When a payment is confirmed Paid: complete the transaction, mark the
-- product Sold, auto-generate the Invoice, and notify both parties
CREATE TRIGGER trg_payment_after_update
AFTER UPDATE ON Payment
FOR EACH ROW
BEGIN
    DECLARE v_product_id INT;
    DECLARE v_buyer_id   INT;
    DECLARE v_seller_id  INT;
    DECLARE v_invoice_no VARCHAR(30);

    IF NEW.payment_status = 'Paid' AND OLD.payment_status <> 'Paid' THEN
        SELECT product_id, buyer_id, seller_id INTO v_product_id, v_buyer_id, v_seller_id
        FROM Transaction WHERE transaction_id = NEW.transaction_id;

        UPDATE Transaction SET status = 'Completed' WHERE transaction_id = NEW.transaction_id;
        UPDATE Product SET status = 'Sold' WHERE product_id = v_product_id;

        SET v_invoice_no = CONCAT('INV-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(NEW.transaction_id, 5, '0'));

        INSERT INTO Invoice (invoice_number, transaction_id, total_amount)
        VALUES (v_invoice_no, NEW.transaction_id, NEW.amount);

        INSERT INTO Notification (user_id, type, transaction_id, message) VALUES
            (v_buyer_id,  'Payment Successful', NEW.transaction_id, 'Your payment was successful. Your invoice is ready.'),
            (v_seller_id, 'Payment Successful', NEW.transaction_id, 'Payment has been received for your product.');
    END IF;
END$$

-- Notify the buyer whenever the delivery status changes
CREATE TRIGGER trg_delivery_after_update
AFTER UPDATE ON Delivery
FOR EACH ROW
BEGIN
    DECLARE v_buyer_id INT;

    IF NEW.delivery_status <> OLD.delivery_status THEN
        SELECT buyer_id INTO v_buyer_id FROM Transaction WHERE transaction_id = NEW.transaction_id;

        INSERT INTO Notification (user_id, type, transaction_id, message)
        VALUES (v_buyer_id, 'Delivery Update', NEW.transaction_id,
                CONCAT('Your order is now: ', NEW.delivery_status));
    END IF;
END$$

DELIMITER ;


-- =====================================================================
-- SECTION 7: EXAMPLE QUERIES (for documentation / reference only)
-- These illustrate the SQL features used throughout the application.
-- They are read-only SELECTs and are safe to run at any time.
-- =====================================================================

-- Keyword search with category filter and price range (LIKE, WHERE, AND)
-- SELECT * FROM vw_product_feed
-- WHERE status = 'Available'
--   AND title LIKE '%calculus%'
--   AND category_name = 'Books'
--   AND price BETWEEN 100 AND 1000
-- ORDER BY created_at DESC
-- LIMIT 20;

-- Aggregate: how many products each category has (GROUP BY, HAVING, COUNT)
-- SELECT category_name, COUNT(*) AS total_products
-- FROM Product p INNER JOIN Category c ON p.category_id = c.category_id
-- GROUP BY category_name
-- HAVING total_products > 0
-- ORDER BY total_products DESC;

-- All bids for a product with buyer details (INNER JOIN)
-- SELECT b.bid_id, b.bid_amount, b.status, u.full_name
-- FROM Bid b INNER JOIN User u ON b.buyer_id = u.user_id
-- WHERE b.product_id = 1
-- ORDER BY b.bid_amount DESC;

-- Every product together with its report count, even if zero (LEFT JOIN)
-- SELECT p.title, COUNT(r.report_id) AS report_count
-- FROM Product p LEFT JOIN Report r ON p.product_id = r.product_id
-- GROUP BY p.product_id;

-- Products priced above the site-wide average price (Subquery)
-- SELECT title, price FROM Product
-- WHERE price > (SELECT AVG(price) FROM Product);

-- Update example: mark a product Sold manually
-- UPDATE Product SET status = 'Sold' WHERE product_id = 1;

-- Delete example: remove a stale pending bid
-- DELETE FROM Bid WHERE status = 'Pending' AND created_at < (NOW() - INTERVAL 30 DAY);

-- Call the stored procedures from PHP like this:
-- CALL sp_place_bid(1, 2, 550.00);
-- CALL sp_accept_bid(3);
-- CALL sp_reject_bid(4);

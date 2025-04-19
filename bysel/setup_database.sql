/*======================================================================
  BySel — COMPLETE SETUP SCRIPT (April 2025)
  ----------------------------------------------------------------------
  • Creates the bysel_db schema from scratch
  • Adds all tables required by the current codebase
  • Seeds four user accounts + one sample stock
  • Sets initial market‑hours and a few 2025 US holidays
  ======================================================================*/

-- ---------------------------------------------------------------------
-- 1.  DATABASE
-- ---------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `bysel_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `bysel_db`;

-- ---------------------------------------------------------------------
-- 2.  USERS  (login + roles)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT          PRIMARY KEY AUTO_INCREMENT,
  `username`      VARCHAR(50)  UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `email`         VARCHAR(100) NOT NULL,
  `role`          ENUM('user','admin') DEFAULT 'user',
  `cash_balance`  DECIMAL(15,2) DEFAULT 10000.00,
  `created_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS `idx_users_username` ON `users`(`username`);

-- ---------------------------------------------------------------------
-- 3.  STOCKS  (includes intraday open / high / low tracking)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stocks` (
  `stock_id`        INT           PRIMARY KEY AUTO_INCREMENT,
  `company_name`    VARCHAR(100)  NOT NULL,
  `ticker`          VARCHAR(5)    UNIQUE NOT NULL,
  `price`           DECIMAL(10,2) NOT NULL,

  `day_open_price`  DECIMAL(10,2) NOT NULL,
  `day_high_price`  DECIMAL(10,2) NOT NULL,
  `day_low_price`   DECIMAL(10,2) NOT NULL,
  `last_update`     TIMESTAMP NULL,

  `volume`          INT           NOT NULL,
  `created_at`      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS `idx_stocks_ticker` ON `stocks`(`ticker`);

-- ---------------------------------------------------------------------
-- 4.  TRANSACTIONS  (completed trades & cash moves)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id`  INT PRIMARY KEY AUTO_INCREMENT,
  `user_id`         INT           NOT NULL,
  `type`            ENUM('buy','sell','deposit','withdraw') NOT NULL,
  `ticker`          VARCHAR(5),
  `shares`          INT,
  `amount`          DECIMAL(15,2) NOT NULL,
  `transaction_date` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE CASCADE,
  FOREIGN KEY (`ticker`) REFERENCES `stocks`(`ticker`)
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5.  MARKET HOURS  (single‑row config)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `market_hours` (
  `id`         TINYINT  PRIMARY KEY DEFAULT 1,
  `open_time`  TIME     NOT NULL,
  `close_time` TIME     NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default 09:30 – 16:00 if not present
INSERT INTO `market_hours` (`id`,`open_time`,`close_time`)
VALUES (1,'09:30:00','16:00:00')
ON DUPLICATE KEY UPDATE open_time = VALUES(open_time);

-- ---------------------------------------------------------------------
-- 6.  MARKET HOLIDAYS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `market_holidays` (
  `holiday_date` DATE PRIMARY KEY,
  `description`  VARCHAR(100) NOT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed a few 2025 US holidays
INSERT INTO `market_holidays` (`holiday_date`,`description`) VALUES
  ('2025-01-01','New Year’s Day'),
  ('2025-07-04','Independence Day'),
  ('2025-11-27','Thanksgiving Day'),
  ('2025-12-25','Christmas Day')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ---------------------------------------------------------------------
-- 7.  PENDING ORDERS  (queued when market is closed)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pending_orders` (
  `order_id`     INT  PRIMARY KEY AUTO_INCREMENT,
  `user_id`      INT  NOT NULL,
  `ticker`       VARCHAR(5) NOT NULL,
  `shares`       INT  NOT NULL,
  `type`         ENUM('buy','sell') NOT NULL,
  `price_at_order` DECIMAL(10,2) NOT NULL,
  `status`       ENUM('pending','cancelled','executed') DEFAULT 'pending',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `executed_at`  TIMESTAMP NULL,

  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE CASCADE,
  FOREIGN KEY (`ticker`)  REFERENCES `stocks`(`ticker`)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS `idx_pending_user`   ON `pending_orders`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_pending_status` ON `pending_orders`(`status`);

-- ---------------------------------------------------------------------
-- 8.  SEED USERS
-- ---------------------------------------------------------------------
INSERT INTO `users`
  (`username`,`password_hash`,`email`,`role`)
VALUES
  -- regular user
  ('lryates',
   '$2y$10$iOMAUbMDenrfNOO9a7NteeUgKIJUn5mHhs8seraBPnJeK3Y2wtgQO', -- boopity
   'lryates@bysel.com',
   'user'),

  -- admin 1
  ('sudolryates',
   '$2y$10$D0hXDles2i5FnF2dXiDalOvtPNSJ70b3o4uG14rUxabkfWnHvVO9S', -- doopity
   'admin.lryates@bysel.com',
   'admin'),

  -- admin 2
  ('JsAdmin',
   '$2y$12$LTJ/IxNxB6l.6f3gah6u5eqjlrx4tjRvuwgd4CXn7X4AujWP66Q5C', -- 1234
   'admin.scoot@bysel.com',
   'admin'),

  -- admin 3
  ('CzAdmin',
   '$2y$12$IlXOOxb1SFu50iP46tD6weF2Gt6B0.9UcfbhVrrexXxWgg.MVkikC', -- 1234
   'admin.ChristopherZ@bysel.com',
   'admin')
ON DUPLICATE KEY UPDATE email = VALUES(email);   -- keep idempotent

-- ---------------------------------------------------------------------
-- 9.  SEED SAMPLE STOCK
-- ---------------------------------------------------------------------
INSERT INTO `stocks`
  (`company_name`,`ticker`,`price`,
   `day_open_price`,`day_high_price`,`day_low_price`,`last_update`,
   `volume`)
VALUES
  ('Apple Inc.','AAPL',150.00,
   150.00,150.00,150.00,NULL,
   1000)
ON DUPLICATE KEY UPDATE price = VALUES(price);

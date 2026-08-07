-- KCE prepaid advertiser wallet migration.
-- Aman dijalankan berulang kali pada MySQL 8 / MariaDB 10.5+.
CREATE TABLE IF NOT EXISTS kce_advertiser_wallets (
  advertiser_id INT NOT NULL,
  balance DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (advertiser_id),
  CONSTRAINT chk_kce_wallet_balance CHECK (balance >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kce_wallet_transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  advertiser_id INT NOT NULL,
  sponsored_content_id BIGINT UNSIGNED DEFAULT NULL,
  ad_event_id BIGINT UNSIGNED DEFAULT NULL,
  transaction_type ENUM('deposit','impression_charge','click_charge','refund','adjustment') NOT NULL,
  amount DECIMAL(14,2) NOT NULL COMMENT 'Positive credit, negative debit',
  balance_before DECIMAL(14,2) NOT NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  payment_reference VARCHAR(190) DEFAULT NULL,
  description VARCHAR(500) DEFAULT NULL,
  created_by_admin VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_kce_wallet_event (ad_event_id),
  UNIQUE KEY uk_kce_wallet_reference (advertiser_id,transaction_type,payment_reference),
  KEY idx_kce_wallet_history (advertiser_id,created_at),
  KEY idx_kce_wallet_campaign (sponsored_content_id,created_at),
  CONSTRAINT fk_kce_wallet_campaign FOREIGN KEY (sponsored_content_id) REFERENCES kce_sponsored_content(id) ON DELETE SET NULL,
  CONSTRAINT fk_kce_wallet_event FOREIGN KEY (ad_event_id) REFERENCES kce_ad_events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

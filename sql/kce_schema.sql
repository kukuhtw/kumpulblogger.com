-- Knowledge Commerce Engine (MySQL 8 / MariaDB 10.5+)
CREATE TABLE IF NOT EXISTS kce_sponsored_content (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  advertiser_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  body TEXT NOT NULL,
  target_url VARCHAR(2048) NOT NULL,
  banner_url VARCHAR(2048) DEFAULT NULL,
  embedding LONGTEXT DEFAULT NULL COMMENT 'JSON float array, exactly 2048 dimensions',
  embedding_model VARCHAR(120) DEFAULT NULL,
  status ENUM('draft','pending_review','active','paused','ended','rejected') NOT NULL DEFAULT 'draft',
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  max_impressions BIGINT UNSIGNED DEFAULT NULL,
  max_clicks BIGINT UNSIGNED DEFAULT NULL,
  impression_unit_cost DECIMAL(14,2) DEFAULT NULL COMMENT 'NULL uses current global KCE setting',
  click_unit_cost DECIMAL(14,2) DEFAULT NULL COMMENT 'NULL uses current global KCE setting',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY idx_kce_active (status, starts_at, ends_at),
  KEY idx_kce_advertiser (advertiser_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kce_settings (
  setting_key VARCHAR(80) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aman dijalankan untuk instalasi KCE lama yang belum memiliki workflow approval.
ALTER TABLE kce_sponsored_content
  MODIFY status ENUM('draft','pending_review','active','paused','ended','rejected') NOT NULL DEFAULT 'draft';
ALTER TABLE kce_sponsored_content
  ADD COLUMN IF NOT EXISTS impression_unit_cost DECIMAL(14,2) DEFAULT NULL AFTER max_clicks,
  ADD COLUMN IF NOT EXISTS click_unit_cost DECIMAL(14,2) DEFAULT NULL AFTER impression_unit_cost;
INSERT IGNORE INTO kce_settings (setting_key, setting_value) VALUES
 ('impression_article_cost','50'), ('sponsored_click_cost','500'),
 ('relevance_threshold','0.35'), ('max_sponsored_results','2');

CREATE TABLE IF NOT EXISTS kce_conversations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL, user_id INT DEFAULT NULL,
  ip_hash CHAR(64) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uk_kce_conversation (public_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kce_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  role ENUM('user','assistant') NOT NULL, content MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), KEY idx_kce_messages_conversation (conversation_id, id),
  CONSTRAINT fk_kce_message_conversation FOREIGN KEY (conversation_id) REFERENCES kce_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kce_article_embeddings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  article_id INT NOT NULL,
  embedding LONGTEXT NOT NULL COMMENT 'JSON float array, exactly 2048 dimensions',
  embedding_model VARCHAR(120) NOT NULL,
  source_hash CHAR(64) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  indexed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uk_kce_article (article_id), KEY idx_kce_article_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kce_message_articles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id BIGINT UNSIGNED NOT NULL,
  article_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT NOT NULL,
  article_url VARCHAR(2048) NOT NULL,
  relevance_score DECIMAL(8,6) NOT NULL DEFAULT 0,
  result_rank TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_kce_message_article (message_id,article_id),
  KEY idx_kce_message_article_order (message_id,result_rank),
  CONSTRAINT fk_kce_message_article_message FOREIGN KEY (message_id) REFERENCES kce_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kce_message_sponsors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id BIGINT UNSIGNED NOT NULL,
  sponsored_content_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  body TEXT NOT NULL,
  banner_url VARCHAR(2048) DEFAULT NULL,
  relevance_score DECIMAL(8,6) NOT NULL DEFAULT 0,
  result_rank TINYINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_kce_message_sponsor (message_id,sponsored_content_id),
  KEY idx_kce_message_sponsor_order (message_id,result_rank),
  CONSTRAINT fk_kce_message_sponsor_message FOREIGN KEY (message_id) REFERENCES kce_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO kce_settings (setting_key,setting_value) VALUES
 ('article_relevance_threshold','0.30'),('max_article_results','3');

CREATE TABLE IF NOT EXISTS kce_ad_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sponsored_content_id BIGINT UNSIGNED NOT NULL,
  conversation_id BIGINT UNSIGNED DEFAULT NULL,
  event_type ENUM('impression','click') NOT NULL,
  charge_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  event_key CHAR(64) NOT NULL, ip_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uk_kce_event (event_key),
  KEY idx_kce_event_report (sponsored_content_id,event_type,created_at),
  CONSTRAINT fk_kce_event_content FOREIGN KEY (sponsored_content_id) REFERENCES kce_sponsored_content(id) ON DELETE CASCADE,
  CONSTRAINT fk_kce_event_conversation FOREIGN KEY (conversation_id) REFERENCES kce_conversations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Saldo prabayar advertiser KCE. Balance adalah cache saldo terkini; seluruh
-- perubahan tetap dicatat pada ledger kce_wallet_transactions.
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

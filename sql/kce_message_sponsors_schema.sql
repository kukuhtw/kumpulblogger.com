-- Snapshot sponsored content yang menyertai jawaban AI dalam history KCE.
-- Aman dijalankan berulang kali setelah kce_messages dan kce_sponsored_content tersedia.
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

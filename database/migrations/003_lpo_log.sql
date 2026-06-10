-- ============================================================
-- Migration 003 — LPO log table
-- Run in phpMyAdmin: reqon_db → SQL tab → execute
-- ============================================================

USE reqon_db;

CREATE TABLE IF NOT EXISTS lpo_log (
    lpo_id          INT           PRIMARY KEY AUTO_INCREMENT,
    requisition_id  INT           NOT NULL,
    lpo_number      VARCHAR(20)   NOT NULL,
    generated_by    INT           NOT NULL,   -- FK → users (must be Mary / Procurement Head)
    generated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    notes           TEXT,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(requisition_id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by)   REFERENCES users(user_id),
    UNIQUE KEY uq_lpo_requisition (requisition_id)   -- one LPO per requisition
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

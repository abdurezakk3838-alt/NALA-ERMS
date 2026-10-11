-- Run this once on an existing NALA ERMS database.
-- New installations should use database.sql instead.
ALTER TABLE incoming_letters
    ADD COLUMN IF NOT EXISTS classification VARCHAR(100) NOT NULL DEFAULT '1.01 General Administration' AFTER receiver_email,
    MODIFY COLUMN classification VARCHAR(100) NOT NULL DEFAULT '1.01 General Administration',
    ADD COLUMN IF NOT EXISTS security_level ENUM('Public','Official','Confidential','Restricted','Top Secret') DEFAULT 'Official' AFTER classification,
    ADD COLUMN IF NOT EXISTS keywords VARCHAR(500) AFTER security_level;

ALTER TABLE outgoing_letters
    MODIFY COLUMN classification VARCHAR(100) NOT NULL DEFAULT '1.01 General Administration',
    ADD COLUMN IF NOT EXISTS security_level ENUM('Public','Official','Confidential','Restricted','Top Secret') DEFAULT 'Official' AFTER classification,
    ADD COLUMN IF NOT EXISTS priority ENUM('Low','Normal','High','Urgent') DEFAULT 'Normal' AFTER security_level,
    ADD COLUMN IF NOT EXISTS keywords VARCHAR(500) AFTER priority;

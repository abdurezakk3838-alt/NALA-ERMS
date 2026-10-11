-- NALA ERMS Database Schema
CREATE DATABASE IF NOT EXISTS nala_erms;
USE nala_erms;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(200) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('head_office','branch','admin','dept_manager','records_officer','director','intermediary','staff') DEFAULT 'staff',
    department VARCHAR(200),
    photo LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Incoming Letters
CREATE TABLE incoming_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_number VARCHAR(50) UNIQUE NOT NULL,
    sender_unique_num VARCHAR(100),
    title VARCHAR(500) NOT NULL,
    description TEXT,
    sender_name VARCHAR(200) NOT NULL,
    receiver_name VARCHAR(200) NOT NULL,
    receiver_email VARCHAR(200),
    classification VARCHAR(100) NOT NULL DEFAULT '1.01 General Administration',
    security_level ENUM('Public','Official','Confidential','Restricted','Top Secret') DEFAULT 'Official',
    keywords VARCHAR(500),
    status ENUM('pending','in-process','approved','rejected','archived','dispatched') DEFAULT 'pending',
    received_date DATE NOT NULL,
    receiver_remark TEXT,
    digital_signature LONGTEXT,
    signed_by VARCHAR(200),
    signed_date DATETIME,
    file_name VARCHAR(500),
    file_data LONGTEXT,
    created_by VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Outgoing Letters
CREATE TABLE outgoing_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_number VARCHAR(50) UNIQUE NOT NULL,
    sender_unique_num VARCHAR(100),
    title VARCHAR(500) NOT NULL,
    description TEXT,
    sender_name VARCHAR(200) NOT NULL,
    receiver_name VARCHAR(200) NOT NULL,
    receiver_email VARCHAR(200),
    classification VARCHAR(100) NOT NULL DEFAULT '1.01 General Administration',
    security_level ENUM('Public','Official','Confidential','Restricted','Top Secret') DEFAULT 'Official',
    priority ENUM('Low','Normal','High','Urgent') DEFAULT 'Normal',
    keywords VARCHAR(500),
    status ENUM('draft','pending_approval','approved','rejected','dispatched') DEFAULT 'draft',
    date DATE NOT NULL,
    manager_remark TEXT,
    digital_signature LONGTEXT,
    signed_by VARCHAR(200),
    signed_date DATETIME,
    file_name VARCHAR(500),
    file_data LONGTEXT,
    created_by VARCHAR(200),
    workflow_stage VARCHAR(100),
    digital_stamp LONGTEXT,
    head_reviewed_by VARCHAR(200),
    head_reviewed_at DATETIME,
    head_review_remark TEXT,
    registration_reference VARCHAR(100),
    registered_by VARCHAR(200),
    registered_at DATETIME,
    dispatch_method VARCHAR(30),
    dispatch_recipient VARCHAR(500),
    dispatch_reference VARCHAR(100),
    dispatched_by VARCHAR(200),
    dispatched_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Internal Memos
CREATE TABLE internal_memos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    sender_name VARCHAR(200) NOT NULL,
    receiver_name VARCHAR(200) NOT NULL,
    receiver_email VARCHAR(200),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    date DATE NOT NULL,
    receiver_remark TEXT,
    digital_signature LONGTEXT,
    signed_by VARCHAR(200),
    signed_date DATETIME,
    file_name VARCHAR(500),
    file_data LONGTEXT,
    created_by VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Audit Logs
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(200) NOT NULL,
    user_role VARCHAR(50),
    action VARCHAR(200) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Workflow Rules (for future expansion)
CREATE TABLE workflow_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    trigger_event VARCHAR(100),
    action_type VARCHAR(100),
    action_config JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Archive Records
CREATE TABLE IF NOT EXISTS archive_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_table VARCHAR(50),
    original_id INT,
    ref_number VARCHAR(50),
    title VARCHAR(500),
    archived_data JSON,
    archived_by VARCHAR(200),
    archived_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    retention_period INT DEFAULT 365,
    expiry_date DATE
);

-- Insert default users
INSERT INTO users (username, full_name, email, password_hash, role, department) VALUES
('head_office', 'Head Office', 'head@nala.gov.et', '$2y$10$slGKI1dbeDRsztWHfGEjyO9JlpW6LEPX0MetjqGOjm3OBdCFWlNtC', 'head_office', 'Head Office'),
('harar_branch', 'Branch Officer - Harar', 'harar@nala.gov.et', '$2y$10$g/0XTLc47qVMRmyUSIPtc.OSCGcSrHYNABBsW6HB4wGII0SeMUPR2', 'branch', 'Harar Branch'),
('dept_manager', 'Dept Manager', 'manager@nala.gov.et', '$2y$10$wJ1Gb4GCC86JuUPeBiyZoOjKsdN87OW8F4kItVj.vmwyLa/YYY3fq', 'dept_manager', 'Records Management'),
('records_officer', 'Records Officer', 'records@nala.gov.et', '$2y$10$2BGJTgCE8b2JXOQtp86o0epqZjHnAYzz1OzM01m4wh96wx72lZlt.', 'records_officer', 'Records Office'),
('director', 'Director General', 'director@nala.gov.et', '$2y$10$sTJKYdG/dg2efYI.u.T4BeGUuqQnOw2Qfy6xaxNw87Vd7KKsRUjL.', 'director', 'Directorate'),
('admin', 'System Administrator', 'admin@nala.gov.et', '$2y$10$7qXzvLEGSHvFgS.u8eXPCOuvGKyecp3wdSnZ9Ey6e2f00dn5girR.', 'admin', 'System Administration');
-- Add to sql/database.sql

-- Additional tables for complete functionality

-- Archive Records
CREATE TABLE IF NOT EXISTS archive_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_table VARCHAR(50),
    original_id INT,
    ref_number VARCHAR(50),
    title VARCHAR(500),
    archived_data JSON,
    archived_by VARCHAR(200),
    archived_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    retention_period INT DEFAULT 365,
    expiry_date DATE
);

-- Workflow Rules
CREATE TABLE IF NOT EXISTS workflow_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    trigger_event VARCHAR(100),
    action_type VARCHAR(100),
    action_config JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document Templates
CREATE TABLE document_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type ENUM('incoming','outgoing','internal') NOT NULL,
    template_content LONGTEXT,
    variables JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- File Tracking (for upload history)
CREATE TABLE file_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_number VARCHAR(50),
    file_name VARCHAR(500),
    file_path VARCHAR(500),
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by VARCHAR(200),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ref_number (ref_number)
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200),
    message TEXT,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
 
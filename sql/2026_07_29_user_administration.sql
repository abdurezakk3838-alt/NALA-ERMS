-- Apply this once to existing NALA ERMS databases created with the older schema.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL AFTER full_name,
    ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) NULL AFTER first_name,
    ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) NULL AFTER middle_name,
    ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT TRUE AFTER photo;

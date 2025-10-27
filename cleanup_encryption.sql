-- Remove all encryption-related tables and columns
-- This script removes the encryption system from the database

-- Step 1: Drop security/backup tables
DROP TABLE IF EXISTS `system_health`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `security_incidents`;
DROP TABLE IF EXISTS `backup_history`;
DROP TABLE IF EXISTS `blocked_ips`;
DROP TABLE IF EXISTS `otp_codes`;

-- Step 2: Remove encrypted columns from users table
ALTER TABLE users DROP COLUMN IF EXISTS `first_name_encrypted`;
ALTER TABLE users DROP COLUMN IF EXISTS `last_name_encrypted`;
ALTER TABLE users DROP COLUMN IF EXISTS `email_encrypted`;
ALTER TABLE users DROP COLUMN IF EXISTS `mobile_number_encrypted`;
ALTER TABLE users DROP COLUMN IF EXISTS `gender_encrypted`;
ALTER TABLE users DROP COLUMN IF EXISTS `birth_date_encrypted`;
ALTER TABLE users DROP COLUMN IF EXISTS `address_encrypted`;

-- Step 3: Check and remove encrypted columns from applications table (if they exist)
ALTER TABLE applications DROP COLUMN IF EXISTS `notes_encrypted`;
ALTER TABLE applications DROP COLUMN IF EXISTS `previous_school_encrypted`;
ALTER TABLE applications DROP COLUMN IF EXISTS `school_year_encrypted`;
ALTER TABLE applications DROP COLUMN IF EXISTS `strand_encrypted`;
ALTER TABLE applications DROP COLUMN IF EXISTS `gpa_encrypted`;
ALTER TABLE applications DROP COLUMN IF EXISTS `address_encrypted`;

SELECT 'Encryption cleanup completed successfully!' AS result;

<?php

return [
    "ALTER TABLE system_admins 
    ADD COLUMN two_factor_secret VARCHAR(255) NULL COMMENT 'Encrypted TOTP secret',
    ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0 COMMENT 'Whether 2FA is enabled',
    ADD COLUMN two_factor_backup_codes TEXT NULL COMMENT 'Encrypted backup codes'"
];


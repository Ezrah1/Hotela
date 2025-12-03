<?php

return [
    "ALTER TABLE license_activations 
    MODIFY COLUMN status ENUM('active', 'expired', 'suspended', 'revoked') DEFAULT 'active',
    ADD COLUMN revocation_reason TEXT NULL COMMENT 'Reason for license revocation' AFTER status,
    ADD COLUMN revoked_at TIMESTAMP NULL COMMENT 'When the license was revoked' AFTER revocation_reason,
    ADD COLUMN revoked_by INT NULL COMMENT 'System admin who revoked the license' AFTER revoked_at"
];


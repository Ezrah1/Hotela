<?php

return [
    "ALTER TABLE license_activations 
    ADD COLUMN package_id INT NULL COMMENT 'Reference to license package',
    ADD COLUMN payment_id INT NULL COMMENT 'Reference to license payment',
    ADD INDEX idx_package (package_id),
    ADD INDEX idx_payment (payment_id),
    ADD CONSTRAINT fk_license_package FOREIGN KEY (package_id) REFERENCES license_packages(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_license_payment FOREIGN KEY (payment_id) REFERENCES license_payments(id) ON DELETE SET NULL"
];


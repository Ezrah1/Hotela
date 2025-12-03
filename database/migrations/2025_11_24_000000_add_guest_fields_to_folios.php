<?php

return [
    "ALTER TABLE folios 
        MODIFY COLUMN reservation_id INT NULL,
        ADD COLUMN guest_email VARCHAR(150) NULL AFTER reservation_id,
        ADD COLUMN guest_phone VARCHAR(50) NULL AFTER guest_email,
        ADD COLUMN guest_name VARCHAR(150) NULL AFTER guest_phone,
        ADD INDEX idx_folios_guest_email (guest_email),
        ADD INDEX idx_folios_guest_phone (guest_phone)",
];


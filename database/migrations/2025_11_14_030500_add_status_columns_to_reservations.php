<?php

return [
    "ALTER TABLE reservations
        ADD COLUMN IF NOT EXISTS check_in_status ENUM('scheduled','checked_in','checked_out') DEFAULT 'scheduled' AFTER payment_status,
        ADD COLUMN IF NOT EXISTS room_status ENUM('pending','in_house','departed') DEFAULT 'pending' AFTER check_in_status;"
];


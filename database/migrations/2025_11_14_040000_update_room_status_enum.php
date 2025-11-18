<?php

return [
    "ALTER TABLE rooms MODIFY COLUMN status ENUM('available','occupied','maintenance','blocked','needs_cleaning') DEFAULT 'available';"
];


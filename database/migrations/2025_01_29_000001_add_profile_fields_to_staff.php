<?php

return [
    "ALTER TABLE staff ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL AFTER address;",
    "ALTER TABLE staff ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(150) NULL AFTER profile_photo;",
    "ALTER TABLE staff ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(50) NULL AFTER emergency_contact_name;",
    "ALTER TABLE staff ADD COLUMN IF NOT EXISTS emergency_contact_relation VARCHAR(50) NULL AFTER emergency_contact_phone;"
];


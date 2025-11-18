<?php

return [
    "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL AFTER status;",
    "ALTER TABLE room_types ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL AFTER amenities;",
];


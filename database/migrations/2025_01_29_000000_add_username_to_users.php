<?php

return [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(100) NULL AFTER email;",
    "CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users(username);"
];


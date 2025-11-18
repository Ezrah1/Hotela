<?php

return [
    "ALTER TABLE expense_categories 
        ADD COLUMN is_petty_cash TINYINT(1) DEFAULT 0 AFTER department;",
];


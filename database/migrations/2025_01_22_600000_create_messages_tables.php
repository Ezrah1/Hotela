<?php

return [
    "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        sender_id INT NOT NULL,
        recipient_id INT NULL,
        recipient_role VARCHAR(50) NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('sent','read','archived','deleted') DEFAULT 'sent',
        is_important TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        deleted_at TIMESTAMP NULL,
        INDEX idx_messages_tenant (tenant_id),
        INDEX idx_messages_sender (sender_id),
        INDEX idx_messages_recipient (recipient_id),
        INDEX idx_messages_recipient_role (recipient_role),
        INDEX idx_messages_status (status),
        INDEX idx_messages_created (created_at),
        CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS message_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(50) NULL,
        file_size INT NULL,
        uploaded_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_message_attachments_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        CONSTRAINT fk_message_attachments_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NULL,
        author_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        target_audience ENUM('all','roles','users') DEFAULT 'all',
        target_roles JSON NULL,
        target_users JSON NULL,
        priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
        status ENUM('draft','published','archived') DEFAULT 'draft',
        publish_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_announcements_tenant (tenant_id),
        INDEX idx_announcements_author (author_id),
        INDEX idx_announcements_status (status),
        INDEX idx_announcements_publish (publish_at),
        CONSTRAINT fk_announcements_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS announcement_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        announcement_id INT NOT NULL,
        user_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY announcement_user_unique (announcement_id, user_id),
        INDEX idx_announcement_reads_user (user_id),
        CONSTRAINT fk_announcement_reads_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
        CONSTRAINT fk_announcement_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
];


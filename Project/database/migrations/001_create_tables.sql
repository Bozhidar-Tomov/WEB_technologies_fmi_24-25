-- Migration: create initial tables for CommandService

-- Commands table stores every broadcast command; latest row is considered active.
CREATE TABLE IF NOT EXISTS `commands` (
    `id`        VARCHAR(40)  PRIMARY KEY,
    `payload`   JSON         NOT NULL,
    `issued_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Active users table keeps track of currently connected users and their last activity.
CREATE TABLE IF NOT EXISTS `active_users` (
    `user_id`    INT         NOT NULL PRIMARY KEY,
    `last_seen`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_agent` VARCHAR(255) NOT NULL,
    CONSTRAINT `fk_active_user_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 
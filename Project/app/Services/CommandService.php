<?php

namespace App\Services;

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../Database/Database.php';

class CommandService
{
    private \PDO $pdo;

    public function __construct()
    {
        // Initialize PDO connection once
        $this->pdo = \App\Database::getConnection();
    }

    /* ------------------------------------------------------------------
     * Commands
     * ------------------------------------------------------------------*/

    /**
     * Broadcast a command to all connected clients. The command payload is
     * persisted in the `commands` table. The most recent command is treated
     * as the active one.
     */
    public function broadcastCommand(array $commandData): bool
    {
        $commandData['id']        ??= uniqid('cmd_');
        $commandData['timestamp'] ??= time();

        $stmt = $this->pdo->prepare(
            "INSERT INTO `commands` (id, payload, issued_at)
             VALUES (:id, :payload, FROM_UNIXTIME(:ts))"
        );

        return $stmt->execute([
            ':id'      => $commandData['id'],
            ':payload' => json_encode($commandData, JSON_UNESCAPED_UNICODE),
            ':ts'      => $commandData['timestamp'],
        ]);
    }

    /**
     * Get the latest command that was broadcast.
     */
    public function getActiveCommand(): ?array
    {
        $stmt = $this->pdo->query(
            "SELECT payload FROM `commands` ORDER BY issued_at DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ? json_decode($row['payload'], true) : null;
    }

    /* ------------------------------------------------------------------
     * Active users
     * ------------------------------------------------------------------*/

    /**
     * Register or refresh an active user. The entry will be updated if it
     * already exists.
     */
    public function registerActiveUser(string $userId): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $stmt = $this->pdo->prepare(
            "INSERT INTO `active_users` (user_id, last_seen, user_agent)
             VALUES (:uid, NOW(), :ua)
             ON DUPLICATE KEY UPDATE last_seen = NOW(), user_agent = VALUES(user_agent)"
        );

        // Also purge stale users on each call
        $this->cleanupInactiveUsers();

        return $stmt->execute([':uid' => $userId, ':ua' => $userAgent]);
    }

    /**
     * Remove an active user explicitly (e.g., on logout or disconnect).
     */
    public function removeActiveUser(string $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `active_users` WHERE user_id = :uid");
        return $stmt->execute([':uid' => $userId]);
    }

    /**
     * Return an associative array of all active users keyed by user_id.
     */
    public function getActiveUsers(): array
    {
        $stmt = $this->pdo->query("SELECT user_id, last_seen, user_agent FROM `active_users`");
        $rows = $stmt->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            $users[$row['user_id']] = [
                'lastSeen'  => strtotime($row['last_seen']),
                'userAgent' => $row['user_agent'],
            ];
        }
        return $users;
    }

    /**
     * Convenience helper.
     */
    public function getActiveUserCount(): int
    {
        $this->cleanupInactiveUsers();
        $stmt = $this->pdo->query("SELECT COUNT(*) AS cnt FROM `active_users`");
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Delete users that have been inactive for more than 60 seconds.
     */
    private function cleanupInactiveUsers(): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM `active_users` WHERE last_seen < (NOW() - INTERVAL 60 SECOND)"
        );
        $stmt->execute();
    }
}

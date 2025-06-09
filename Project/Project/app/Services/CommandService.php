<?php

namespace App\Services;

require_once __DIR__ . '/../utils.php';

class CommandService
{
    private $commandsDir;
    private $commandHistoryDir;
    private $acksDir;
    private $activeUsersFile;

    public function __construct()
    {
        $this->commandsDir = __DIR__ . '/../Database/commands';
        $this->commandHistoryDir = "{$this->commandsDir}/command_history";
        $this->acksDir = __DIR__ . '/../Database/acks';
        $this->activeUsersFile = __DIR__ . '/../Database/active_users.json';

        foreach ([$this->commandsDir, $this->commandHistoryDir, $this->acksDir, dirname($this->activeUsersFile)] as $dir) {
            ensureDirectoryExists($dir);
        }
    }

    public function broadcastCommand(array $commandData): bool
    {
        $commandData['id'] ??= uniqid('cmd_');
        $commandData['timestamp'] = time();

        $activeCommandFile = "{$this->commandsDir}/active_command.json";
        $historyFile = "{$this->commandHistoryDir}/{$commandData['id']}.json";

        return saveJsonFile($activeCommandFile, $commandData) &&
               saveJsonFile($historyFile, $commandData);
    }


    public function getActiveCommand(): ?array
    {
        return readJsonFile("{$this->commandsDir}/active_command.json");
    }

    public function registerActiveUser(string $userId): bool
    {
        $this->cleanupInactiveUsers();

        $users = $this->getActiveUsers();
        $users[$userId] = [
            'lastSeen'   => time(),
            'userAgent'  => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        return saveJsonFile($this->activeUsersFile, $users);
    }

    public function removeActiveUser(string $userId): bool
    {
        $users = $this->getActiveUsers();
        if (!isset($users[$userId])) return true;

        unset($users[$userId]);
        return saveJsonFile($this->activeUsersFile, $users);
    }

    public function getActiveUsers(): array
    {
        return readJsonFile($this->activeUsersFile) ?? [];
    }

    public function getActiveUserCount(): int
    {
        $this->cleanupInactiveUsers();
        return count($this->getActiveUsers());
    }

    private function cleanupInactiveUsers(): void
    {
        $users = $this->getActiveUsers();
        $now = time();
        $timeout = 60;
        $updated = false;

        foreach ($users as $userId => $data) {
            if ($now - ($data['lastSeen'] ?? 0) > $timeout) {
                unset($users[$userId]);
                $updated = true;
            }
        }

        if ($updated) {
            saveJsonFile($this->activeUsersFile, $users);
        }
    }
}

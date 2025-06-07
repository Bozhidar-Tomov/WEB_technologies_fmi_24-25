<?php

function ensureDirectoryExists(string $dir): void
{
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: {$dir}");
        }
    }
        
    if (!is_writable($dir)) {
        throw new \RuntimeException("Directory is not writable: {$dir}");
    }
}
function ensureFileExists(string $file): void
{
    if (!file_exists($file)) {
        if (!touch($file)) {
            throw new \RuntimeException("Failed to create file: {$file}");
        }
    }
        
    if (!is_writable($file)) {
        throw new \RuntimeException("File is not writable: {$file}");
    }
}

function saveJsonFile(string $filePath, array $data, bool $useLock = true): bool
{
    $tempFile = "{$filePath}.tmp";
    $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $json = json_encode($data, $options);
    
    if ($json === false) return false;
    
    $flags = $useLock ? LOCK_EX : 0;
    
    if (file_put_contents($tempFile, $json, $flags) === false) return false;
    
    return rename($tempFile, $filePath);
}

function readJsonFile(string $filePath): ?array
{
    if (!is_readable($filePath)) return null;

    $content = file_get_contents($filePath);
    if ($content === false || trim($content) === '') return null;

    $data = json_decode($content, true);
    return is_array($data) ? $data : null;
}
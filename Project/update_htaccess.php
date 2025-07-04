<?php
// update_htaccess.php

$publicDir = __DIR__ . '/public';
$htaccessFile = $publicDir . '/.htaccess';

// Get the relative path from the document root to the public directory
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? getenv('DOCUMENT_ROOT') ?: '');
$publicReal = realpath($publicDir);

if (!$docRoot || !$publicReal) {
    die("Could not determine document root or public directory.\n");
}

$relative = str_replace('\\', '/', str_replace($docRoot, '', $publicReal));
if ($relative === '') $relative = '/';
if (substr($relative, -1) !== '/') $relative .= '/';

$rewriteBase = "RewriteBase $relative";

// Read .htaccess
$lines = file_exists($htaccessFile) ? file($htaccessFile) : [];
$found = false;
foreach ($lines as &$line) {
    if (stripos($line, 'RewriteBase') === 0) {
        $line = $rewriteBase . "\n";
        $found = true;
        break;
    }
}
unset($line);

if (!$found) {
    // Insert after RewriteEngine On, or at the top
    $inserted = false;
    foreach ($lines as $i => $line) {
        if (stripos($line, 'RewriteEngine On') === 0) {
            array_splice($lines, $i + 1, 0, $rewriteBase . "\n");
            $inserted = true;
            break;
        }
    }
    if (!$inserted) {
        array_unshift($lines, $rewriteBase . "\n");
    }
}

file_put_contents($htaccessFile, implode('', $lines));
echo "Updated RewriteBase to: $relative\n"; 
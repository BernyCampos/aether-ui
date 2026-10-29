<?php

declare(strict_types=1);

$failed = false;
$root = dirname(__DIR__);
foreach (['src', 'tests', 'tools', 'lang'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory)) as $file) {
        if ($file->getExtension() !== 'php') continue;
        passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $status);
        $failed = $failed || $status !== 0;
    }
}
exit($failed ? 1 : 0);

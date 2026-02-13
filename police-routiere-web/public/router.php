<?php

// Router for PHP built-in web server.
// This ensures Symfony routes (including AssetMapper /assets/*) work when using `php -S`.

$publicDir = __DIR__;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($path)) {
    $file = realpath($publicDir.$path);
    if ($file !== false && str_starts_with($file, $publicDir) && is_file($file)) {
        return false;
    }
}

return require $publicDir.'/index.php';

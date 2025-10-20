<?php
// router.php

// Si le fichier ou dossier demandé existe, on le sert normalement
if (php_sapi_name() === 'cli-server') {
    $path = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($path)) {
        return false;
    }
}

// Sinon, on redirige tout vers index.php
require __DIR__ . '/index.php';

<?php

if (preg_match('/\.(?:png|jpg|jpeg|gif|js|css|woff2|woff|ttf|ico)$/', $_SERVER['REQUEST_URI'])) {
    return false;
}

include __DIR__ . '/index.php';

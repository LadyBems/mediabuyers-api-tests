<?php
// Suite-level bootstrap. Loads environment variables from .env so that
// configuration (base URL, future credentials) is resolved at runtime
// rather than hard-coded into tests.

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

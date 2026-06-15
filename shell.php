#!/usr/bin/env php
<?php
chdir(__DIR__);
if(!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo 'Please run "composer install" before running this';
    exit(1);
}
if(!isset($_SERVER['argv'][1])) {
    echo "Usage: php shell.php <route> [args]\n";
    echo "Example: php shell.php /migrate\n";
    exit(1);
}
require_once __DIR__ . '/vendor/autoload.php';
LarkFrame\App::run(LarkFrame\Consts::RUN_TYPE_SHELL);

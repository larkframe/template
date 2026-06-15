<?php
if(!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    echo 'Please run "composer install" before running this';
    exit(1);
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization,X-Requested-With,If-Modified-Since,Keep-Alive,User-Agent,Cache-Control,Content-Type");

//define("ROOT_PATH", dirname(__DIR__));

require_once dirname(__DIR__) . '/vendor/autoload.php';
LarkFrame\App::run(LarkFrame\Consts::RUN_TYPE_WEB);
<?php
if(!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    echo 'Please run "composer install" before running this';
    exit(1);
}

// FPM 模式显式定义 ROOT_PATH：cwd 取决于 fastcgi 配置，
// 自动探测（vendor/ + 入口文件并存）在部分部署形态下会失败
define("ROOT_PATH", dirname(__DIR__));

// CORS 说明：中间件（含 CorsMiddleware）仅在 Server 模式生效，FPM 模式如需跨域
// 请在 Nginx 层配置；注意 Access-Control-Allow-Origin: * 与 Allow-Credentials: true
// 组合会被浏览器拒绝，不可同时使用

require_once dirname(__DIR__) . '/vendor/autoload.php';
LarkFrame\App::run(LarkFrame\Consts::RUN_TYPE_WEB);

<?php

use LarkFrame\Route;

// 首页（原生 PHP 模板）
Route::any('/', [App\Controller\DefaultController::class, 'index']);

// Twig 模板示例
Route::get('/user/test', [App\Controller\DefaultController::class, 'twig']);

// API 示例
Route::get('/api', [App\Controller\DefaultController::class, 'api']);
Route::get('/api/db', [App\Controller\DefaultController::class, 'db']);
Route::get('/api/cache', [App\Controller\DefaultController::class, 'cache']);
// 该路由每次调用都会向 Redis 队列投递任务，匿名可调用时必须限流：
// ThrottleMiddleware 按 IP + 路径做固定窗口限流（10 次/分钟），超限返回 429
Route::get('/api/queue', [App\Controller\DefaultController::class, 'queue'])
    ->middleware(App\Middleware\ThrottleMiddleware::class);
Route::get('/api/util', [App\Controller\DefaultController::class, 'util']);

// 带参数的路由
Route::get('/users/{id:\d+}', function ($request) {
    $id = $request->route()->param('id');
    return json(['user_id' => $id]);
});

// 命令行路由（action 是否带 Action 后缀均可，框架自动补齐；同一文件内保持一致风格）
Route::shell('/migrate', [App\Controller\ShellController::class, 'migrate']);
Route::shell('/cache/clear', [App\Controller\ShellController::class, 'cacheClear']);

<?php

use LarkFrame\Route;

// 首页
Route::any('/', [App\Controller\DefaultController::class, 'index']);

// API 示例
Route::get('/api', [App\Controller\DefaultController::class, 'api']);
Route::get('/api/db', [App\Controller\DefaultController::class, 'db']);
Route::get('/api/cache', [App\Controller\DefaultController::class, 'cache']);
Route::get('/api/queue', [App\Controller\DefaultController::class, 'queue']);
Route::get('/api/util', [App\Controller\DefaultController::class, 'util']);

// 带参数的路由
Route::get('/users/{id:\d+}', function ($request) {
    $id = $request->route()->param('id');
    return json(['user_id' => $id]);
});

// 命令行路由
Route::shell('/migrate', [App\Controller\ShellController::class, 'migrate']);
Route::shell('/cache/clear', [App\Controller\ShellController::class, 'cacheClearAction']);

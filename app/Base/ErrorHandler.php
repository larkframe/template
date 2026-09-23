<?php

namespace App\Base;

/**
 * 应用错误处理器
 *
 * 继承框架核心 ErrorHandler，通过 config/config.php 的 error 项配置：
 *
 *   'error' => [
 *       'catch'    => true,                          // 所有模式统一注册自定义错误处理器
 *       'handler'  => \App\Base\ErrorHandler::class, // 错误处理器类
 *       'options'  => [
 *           'logger'      => fn(string $msg) => \LarkFrame\Log::error($msg), // 自定义日志器
 *           'error_types' => E_ALL,                                            // 错误级别掩码
 *       ],
 *   ],
 *
 * 支持的 options：
 *   - logger: callable(string): void  自定义日志器，默认用 Worker::log
 *   - error_types: int               错误级别掩码，默认 error_reporting()
 *
 * 所有运行模式（Server/Task/Web/Shell）均通过 App::registerErrorHandler() 统一注册：
 *   - Server/Task: throwOnError=true，错误转 ErrorException 由 onMessage try-catch 记录
 *   - Web/Shell:   throwOnError=false，直接记录日志并抑制错误
 */
class ErrorHandler extends \LarkFrame\ErrorHandler
{
}

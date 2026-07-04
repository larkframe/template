<?php

namespace App\Base;

/**
 * 应用错误处理器
 *
 * 继承框架核心 ErrorHandler，通过 config/config.php 的 error 项配置：
 *
 *   'error' => [
 *       'catch'    => true,                          // Web/Shell 模式下是否注册自定义错误处理器
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
 * Server 模式在 onWorkerStart 内单独注册错误处理器，不受此配置影响。
 */
class ErrorHandler extends \LarkFrame\ErrorHandler
{
}

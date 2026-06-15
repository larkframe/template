<?php
namespace App\Base;
use ErrorException;

class ErrorHandler extends \LarkFrame\ErrorHandler
{
    protected static $options = [
        'log_errors'        => true,
        'display_errors'    => false,
        'error_log'         => null,
        'fatal_shutdown'    => true,
        'convert_errors'    => true,
        'exception_handler' => null,
        'error_handler'     => null,
        'shutdown_handler'  => null,
        'cli_exit_code'     => 1,
    ];
    protected static $originalHandlers = [
        'exception' => null,
        'error'     => null,
    ];
    
    protected static $isRegistered = false;
    protected static $handledFatal = false;
    protected static $inShutdown = false;

    /**
     * 注册错误处理器
     */
    public static function register(array $options = [])
    {
        if (self::$isRegistered) {
            return;
        }
        
        // 合并配置
        self::$options = array_merge(self::$options, $options);
        
        // 设置错误日志
        if (self::$options['error_log']) {
            ini_set('error_log', self::$options['error_log']);
        }
        
        // 保存原始处理器
        self::$originalHandlers['exception'] = set_exception_handler([__CLASS__, 'handleException']);
        self::$originalHandlers['error'] = set_error_handler([__CLASS__, 'handleError']);
        
        // 注册关闭函数
        if (self::$options['fatal_shutdown']) {
            register_shutdown_function([__CLASS__, 'handleShutdown']);
        }
        
        self::$isRegistered = true;
    }
    
    /**
     * 恢复原始处理器
     */
    public static function restore()
    {
        if (self::$isRegistered) {
            set_exception_handler(self::$originalHandlers['exception']);
            set_error_handler(self::$originalHandlers['error']);
            self::$isRegistered = false;
        }
    }
    
    /**
     * 异常处理
     */
    public static function handleException($exception)
    {
        // 调用自定义异常处理器
        if (is_callable(self::$options['exception_handler'])) {
            call_user_func(self::$options['exception_handler'], $exception);
        } else {
            self::logException($exception);
            self::displayException($exception);
        }
        
        // 调用原始异常处理器
        if (is_callable(self::$originalHandlers['exception'])) {
            call_user_func(self::$originalHandlers['exception'], $exception);
        }
        
        // CLI模式下退出
        if (self::isCliMode()) {
            exit(self::$options['cli_exit_code']);
        }
    }
    
    /**
     * 错误处理
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // 忽略被抑制的错误
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        // 调用自定义错误处理器
        if (is_callable(self::$options['error_handler'])) {
            $result = call_user_func(
                self::$options['error_handler'], 
                $errno, $errstr, $errfile, $errline
            );
            
            // 如果返回true，表示已完全处理，不再继续
            if ($result === true) {
                return true;
            }
        }
        
        // 记录错误
        $errorString = self::formatError($errno, $errstr, $errfile, $errline);
        self::logError($errorString);
        
        // 显示错误
        if (self::$options['display_errors']) {
            self::displayError($errno, $errstr, $errfile, $errline);
        }
        
        // 将错误转换为异常
        if (self::$options['convert_errors'] && self::isFatalError($errno)) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        
        // 调用原始错误处理器
        if (is_callable(self::$originalHandlers['error'])) {
            return call_user_func(
                self::$originalHandlers['error'], 
                $errno, $errstr, $errfile, $errline
            );
        }
        
        // 不要执行PHP内部错误处理
        return true;
    }
    
    /**
     * 关闭处理
     */
    public static function handleShutdown()
    {
        // 防止递归调用
        if (self::$inShutdown) {
            return;
        }
        
        self::$inShutdown = true;
        
        $error = error_get_last();
        
        if ($error && self::isFatalError($error['type']) && !self::$handledFatal) {
            self::$handledFatal = true;
            
            // 调用自定义关闭处理器
            if (is_callable(self::$options['shutdown_handler'])) {
                call_user_func(self::$options['shutdown_handler'], $error);
            }
            
            // 格式化并记录致命错误
            $errorString = self::formatFatalError($error);
            self::logError($errorString);
            
            if (self::$options['display_errors']) {
                self::displayFatalError($error);
            }
            
            // CLI模式下退出
            if (self::isCliMode()) {
                exit(self::$options['cli_exit_code'] + 1);
            }
        }
        
        self::$inShutdown = false;
    }
    
    /**
     * 格式化错误信息
     */
    protected static function formatError($errno, $errstr, $errfile, $errline)
    {
        $type = self::ERROR_TYPES[$errno] ?? 'E_UNKNOWN';
        return sprintf("[%s] %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $type,
            $errstr,
            $errfile,
            $errline
        );
    }
    
    /**
     * 格式化致命错误
     */
    protected static function formatFatalError(array $error)
    {
        $type = self::ERROR_TYPES[$error['type']] ?? 'E_FATAL';
        return sprintf("[%s] FATAL %s: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $type,
            $error['message'],
            $error['file'],
            $error['line']
        );
    }
    
    /**
     * 记录异常
     */
    protected static function logException($exception)
    {
        if (!self::$options['log_errors']) {
            return;
        }
        
        $logEntry = sprintf(
            "EXCEPTION [%s] %s: %s in %s:%d\nStack trace:\n%s",
            get_class($exception),
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        error_log($logEntry);
    }
    
    /**
     * 记录错误
     */
    protected static function logError($message)
    {
        if (self::$options['log_errors']) {
            error_log($message);
        }
    }
    
    /**
     * 显示异常
     */
    protected static function displayException($exception)
    {
        if (!self::$options['display_errors']) {
            return;
        }
        
        if (self::isCliMode()) {
            $output = "\n\033[1;41m UNCAUGHT EXCEPTION \033[0m\n";
            $output .= sprintf(
                " \033[1;33m%s\033[0m: %s\n",
                get_class($exception),
                $exception->getMessage()
            );
            $output .= sprintf(
                " in \033[1m%s\033[0m on line \033[1m%d\033[0m\n\n",
                $exception->getFile(),
                $exception->getLine()
            );
            $output .= "Stack trace:\n";
            $output .= $exception->getTraceAsString() . "\n";
            
            self::writeCliOutput($output);
        } else {
            echo '<div style="background:#fdd; border:2px solid #c00; padding:15px; margin:10px; font-family:sans-serif;">';
            echo '<h3 style="color:#c00; margin:0 0 10px 0;">Uncaught Exception</h3>';
            echo '<p><strong>Type:</strong> ' . get_class($exception) . '</p>';
            echo '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . $exception->getFile() . '</p>';
            echo '<p><strong>Line:</strong> ' . $exception->getLine() . '</p>';
            echo '<pre style="background:#fee; padding:10px; border:1px solid #c00; overflow:auto;">' 
                . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
    }
    
    /**
     * 显示错误
     */
    protected static function displayError($errno, $errstr, $errfile, $errline)
    {
        if (self::isCliMode()) {
            $type = self::ERROR_TYPES[$errno] ?? 'E_UNKNOWN';
            $output = sprintf(
                "\n\033[1;33m%s\033[0m: %s in %s on line %d\n",
                $type,
                $errstr,
                $errfile,
                $errline
            );
            
            self::writeCliOutput($output);
        } else {
            $type = self::ERROR_TYPES[$errno] ?? 'E_UNKNOWN';
            echo '<div style="background:#ffd; border:2px solid #990; padding:10px; margin:10px; font-family:sans-serif;">';
            echo '<h3 style="color:#990; margin:0 0 10px 0;">PHP Error</h3>';
            echo '<p><strong>Type:</strong> ' . $type . '</p>';
            echo '<p><strong>Message:</strong> ' . htmlspecialchars($errstr) . '</p>';
            echo '<p><strong>File:</strong> ' . $errfile . '</p>';
            echo '<p><strong>Line:</strong> ' . $errline . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * 显示致命错误
     */
    protected static function displayFatalError(array $error)
    {
        if (self::isCliMode()) {
            $type = self::ERROR_TYPES[$error['type']] ?? 'E_FATAL';
            $output = sprintf(
                "\n\033[1;41m FATAL ERROR: %s \033[0m\n",
                $type
            );
            $output .= sprintf(" Message: %s\n", $error['message']);
            $output .= sprintf(" File:    %s\n", $error['file']);
            $output .= sprintf(" Line:    %d\n", $error['line']);
            
            self::writeCliOutput($output);
        } else {
            $type = self::ERROR_TYPES[$error['type']] ?? 'E_FATAL';
            echo '<div style="background:#faa; border:2px solid #c00; padding:15px; margin:10px; font-family:sans-serif;">';
            echo '<h3 style="color:#c00; margin:0 0 10px 0;">Fatal Error</h3>';
            echo '<p><strong>Type:</strong> ' . $type . '</p>';
            echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
            echo '<p><strong>File:</strong> ' . $error['file'] . '</p>';
            echo '<p><strong>Line:</strong> ' . $error['line'] . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * 检查是否是致命错误
     */
    protected static function isFatalError($errno)
    {
        return in_array($errno, [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR]);
    }
    
    /**
     * 检查是否CLI模式
     */
    protected static function isCliMode()
    {
        return PHP_SAPI === 'cli';
    }
    
    /**
     * CLI模式输出
     */
    protected static function writeCliOutput($message)
    {
        // 尝试写入STDERR，失败则使用STDOUT
        if (defined('STDERR')) {
            fwrite(STDERR, $message);
        } else {
            echo $message;
        }
    }
}
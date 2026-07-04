<?php

return [
    'app' => [
        'runtime_path' => 'runtime',
        'name' => 'larkframe',
        // 生产环境默认关闭调试，避免堆栈泄漏；开发时通过 config.dev.php 覆盖为 true
        'debug' => false,
    ],
    'server' => [
        'daemonize' => false,
        'socketName' => 'http://0.0.0.0:8080',
        'pidFile' => 'server.pid',
        'stdoutFile' => 'server.stdout.log',
        'logFile' => 'server.log',
        'accessLog' => 'default',
        'worker' => [
            'count' => 1,
            'reusePort' => true,
        ],
        'eventLoopClass' => LarkFrame\Events\Select::class,
        'middleware' => [
            \App\Middleware\CorsMiddleware::class,
        ],
        'static_middleware' => [

        ]
    ],

    'view' => [
        'handler' => LarkFrame\View\Raw::class,
        'options' => [
            'view_suffix' => 'html',
        ],
    ],

    'error' => [
        // Web/Shell 模式下注册自定义错误处理器；Server 模式在 onWorkerStart 内单独注册
        'catch' => true,
        'handler' => \App\Base\ErrorHandler::class,
        'options' => [
            // 错误日志写入 Monolog 通道（runtime/logs/access.log），避免 Worker::log 直接输出到 STDOUT 污染响应
            'logger' => fn(string $msg) => \LarkFrame\Log::error($msg),
            'error_types' => E_ALL,
        ]
    ],

    'log' => [
        'default' => [
            'handlers' => [
                [
                    'class' => Monolog\Handler\RotatingFileHandler::class,
                    'constructor' => [
                        runtime_path() . '/logs/access.log',
                        7, //$maxFiles
                        Monolog\Logger::DEBUG,
                    ],
                    'formatter' => [
                        'class' => LarkFrame\LogFormatter::class,
                        'constructor' => [null, 'Y-m-d H:i:s', true, false],
                    ],
                ]
            ],
        ],
    ],
    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => runtime_path('cache')
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default'
            ],
            'array' => [
                'driver' => 'array'
            ]
        ]
    ],

    'redis' => [
        'default' => [
            'password' => '',
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ]
    ],

    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'larkframe',
                'username' => 'larkframe',
                'password' => 'larkframe',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
                'options' => [
                    PDO::ATTR_EMULATE_PREPARES => false, // Must be false for Swoole and Swow drivers.
                ],
                'pool' => [
                    'max_connections' => 5,
                    'min_connections' => 1,
                    'wait_timeout' => 3,
                    'idle_timeout' => 60,
                    'heartbeat_interval' => 50,
                ],
            ],
        ],
    ],

    'container' => new LarkFrame\Container(),

    'queue' => [
        'default' => 'default',
        'driver' => 'redis',
        'retry_after' => 60,
        'max_tries' => 3,
    ],

];
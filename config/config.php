<?php

return [
    'app' => [
        // 相对路径基于 ROOT_PATH 解析；注意 config.php 自身内的日志路径在配置加载前求值，
        // 始终使用默认 runtime 目录（见 core helper.php runtime_path 注释）
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
    ],

    // CORS 白名单：仅命中列表的 Origin 才会收到跨域响应头（见 App\Middleware\CorsMiddleware）
    'cors' => [
        'allow_origins' => [
            // 'http://localhost:5173',   // 本地前端开发地址，按需修改
        ],
    ],

    'view' => [
        'handler' => LarkFrame\View\Raw::class,
        'options' => [
            // Raw 引擎模板为 template/default/index.php 等 .php 文件，默认后缀必须为 php；
            // Twig 模板（template/user/test.html）调用时显式传 'html' 后缀
            'view_suffix' => 'php',
        ],
    ],

    'error' => [
        // 所有模式（Server/Task/Web/Shell）统一注册错误处理器，日志按 logger 配置写入文件
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
                        // 闭包延迟求值：本文件被 require 时配置尚未加载，
                        // 直接调 runtime_path() 会取默认目录；闭包在 handler 实例化时求值
                        fn() => runtime_path('logs/access.log'),
                        7, //$maxFiles
                        Monolog\Logger::DEBUG,
                        true, //$bubble
                        null, //$filePermission
                        // useLocking：必须为 true。多 worker（server.worker.count > 1 或
                        // reusePort）并发写同一日志文件时，无 flock 会出现行撕裂/内容交错
                        true, //$useLocking
                    ],
                    'formatter' => [
                        'class' => LarkFrame\LogFormatter::class,
                        // 参数：format, dateFormat, allowInlineLineBreaks, ignoreEmptyContextAndExtra
                        // allowInlineLineBreaks 必须为 false：置 true 时 message 内的换行会原样落盘，
                        // 单条日志被拆成多行，异常文本或用户输入即可伪造出额外的日志行。
                        // 需要多行异常堆栈时改传第 5 个参数 includeStacktraces=true（它会自动开启内联换行）
                        'constructor' => [null, 'Y-m-d H:i:s', false, false],
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
                // 闭包延迟求值，理由同上
                'path' => fn() => runtime_path('cache')
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
        // 队列依赖 Redis：默认 client 为 phpredis（需 ext-redis）；
        // 未安装 ext-redis 时在 redis 配置中增加 'client' => 'predis'（composer 包已随 core 安装）
        'driver' => 'redis',
        'retry_after' => 60,
        'max_tries' => 3,
    ],

];
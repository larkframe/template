<?php

/**
 * Task 模式配置
 *
 * 每个 task 对应一个常驻内存 Worker 进程
 * 启动：php task.php <taskname>
 * 停止：kill $(cat runtime/task-<taskname>.pid)
 */
return [
    'consume' => [
        'handler' => \App\Task\ConsumeTask::class,
        'options' => [
            'queue' => 'emails',
            'interval' => 1,
        ],
        'daemonize' => false,
        'worker' => [
            'count' => 1,
        ],
        'pidFile' => 'task-consume.pid',
        'stdoutFile' => 'task-consume.stdout.log',
        'logFile' => 'task-consume.log',
    ],

    'cron' => [
        'handler' => \App\Task\CronTask::class,
        'options' => [
            'interval' => 10,
        ],
        'daemonize' => false,
        'worker' => [
            'count' => 1,
        ],
        'pidFile' => 'task-cron.pid',
        'stdoutFile' => 'task-cron.stdout.log',
        'logFile' => 'task-cron.log',
    ],
];

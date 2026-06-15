<?php

namespace App\Task;

use LarkFrame\Worker;
use LarkFrame\Queue;
use App\Job\SendEmailJob;

/**
 * 定时任务示例
 *
 * 常驻内存 Worker 进程，按配置的时间间隔执行任务
 *
 * 启动：php task.php cron
 * 停止：php task.php cron stop
 */
class CronTask
{
    public static function run(array $options, array $args): void
    {
        $interval = $options['interval'] ?? 10; // 默认 10 秒

        Worker::log("[CronTask] Starting, interval: {$interval}s");

        // 立即执行一次
        static::tick();

        // 定时执行
        Worker::$globalEvent->repeat($interval, function () {
            static::tick();
        });
    }

    protected static function tick(): void
    {
        $now = date('Y-m-d H:i:s');

        // 定时推送邮件到队列，供 ConsumeTask 消费
        $jobId = Queue::push('emails', SendEmailJob::class, [
            'to' => 'user' . rand(1, 100) . '@example.com',
            'subject' => "Daily report at {$now}",
        ]);

        Worker::log("[CronTask] {$now} - pushed email job: {$jobId}");
    }
}

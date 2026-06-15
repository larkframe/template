<?php

namespace App\Task;

use LarkFrame\Worker;
use LarkFrame\Queue;

/**
 * 队列消费任务示例
 *
 * 常驻内存 Worker 进程，消费 Redis 队列中的消息
 *
 * 启动：php task.php consume
 * 停止：php task.php consume stop
 */
class ConsumeTask
{
    public static function run(array $options, array $args): void
    {
        $queue = $options['queue'] ?? 'emails';
        $interval = $options['interval'] ?? 1; // 秒

        Worker::log("[ConsumeTask] Starting, queue: {$queue}, interval: {$interval}s");

        Worker::$globalEvent->repeat($interval, function () use ($queue) {
            $job = Queue::pop($queue);
            if ($job) {
                Worker::log("[ConsumeTask] Processing job: " . $job->getName());
                $job->fire();
            }
        });
    }
}

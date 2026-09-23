<?php

namespace App\Task;

use LarkFrame\Worker;
use LarkFrame\Queue;
use LarkFrame\Queue\Worker as QueueWorker;
use Throwable;

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

        // 复用框架 Queue\Worker 的任务处理逻辑（max_tries 判定 + 失败/重试分支），
        // 避免在此重复实现导致 queue.max_tries 配置失效、任务首次失败即进 failed 队列
        $consumer = new QueueWorker(Queue::driver(), config('queue', []));

        Worker::log("[ConsumeTask] Starting, queue: {$queue}, interval: {$interval}s");

        Worker::$globalEvent->repeat($interval, function () use ($queue, $consumer) {
            try {
                $job = Queue::pop($queue);
            } catch (Throwable $e) {
                // Redis 故障：记录并等待下一个轮询周期，不让异常穿透事件循环回调
                Worker::log("[ConsumeTask] Queue pop failed: " . $e->getMessage());
                return;
            }

            if (!$job) {
                return;
            }

            Worker::log("[ConsumeTask] Processing job: " . $job->getName());
            $consumer->processJob($job);
        });
    }
}

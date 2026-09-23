<?php

namespace App\Job;

use LarkFrame\Queue\Job;
use LarkFrame\Worker;

/**
 * 发送邮件队列任务
 *
 * 用法：
 *   Queue::push('emails', SendEmailJob::class, ['to' => 'user@example.com', 'subject' => 'Hello']);
 */
class SendEmailJob
{
    public function handle(Job $job, mixed $data): void
    {
        $to = $data['to'] ?? '';
        $subject = $data['subject'] ?? 'No Subject';

        // 常驻 Worker 内禁止 echo：daemonize 模式下 STDOUT 丢失，且会污染同步执行时的 HTTP 响应体
        Worker::log("[SendEmailJob] Sending email to: {$to}, subject: {$subject}");

        // TODO: 实现邮件发送逻辑
        // 例如使用 App\Library\Notify 发送邮件

        $job->ack();
    }
}

<?php

namespace App\Job;

use LarkFrame\Queue\Job;

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

        echo "[SendEmailJob] Sending email to: {$to}, subject: {$subject}\n";

        // TODO: 实现邮件发送逻辑
        // 例如使用 App\Library\Notify 发送邮件

        $job->ack();
    }
}

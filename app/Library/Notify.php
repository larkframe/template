<?php

namespace App\Library;

use Genkgo\Mail;
use LarkFrame\Library;
use LarkFrame\Log;
use Throwable;

/**
 * 通知类库（配置见 config/notify.php）
 */
class Notify extends Library
{
    /**
     * 发送邮件
     *
     * SMTP 连接/认证/发送失败时记录错误日志并返回 false，
     * 不向调用方抛异常（通知失败不应中断主业务流程）。
     */
    public function sendByMail(string $subject, string $body, string $toMail, string $toName = ''): bool
    {
        $config = $this->config['mail'] ?? [];
        foreach (['mail_addr', 'protocol', 'user', 'pass', 'host'] as $key) {
            if (empty($config[$key])) {
                Log::warning("[Notify] mail config missing key: {$key}");
                return false;
            }
        }

        try {
            $message = (new Mail\MessageBodyCollection($body))
                ->createMessage()
                ->withHeader(new Mail\Header\Subject($subject))
                ->withHeader(Mail\Header\From::fromEmailAddress($config['mail_addr']))
                ->withHeader(Mail\Header\To::fromSingleRecipient($toMail, $toName));

            // 凭据含 @ : / 等保留字符时必须 URL 编码，否则 DSN 解析错乱；端口默认 587
            $dsn = sprintf(
                '%s://%s:%s@%s:%d/',
                $config['protocol'],
                rawurlencode($config['user']),
                rawurlencode($config['pass']),
                $config['host'],
                (int)($config['port'] ?? 587)
            );

            $transport = new Mail\Transport\SmtpTransport(
                Mail\Protocol\Smtp\ClientFactory::fromString($dsn)->newClient(),
                Mail\Transport\EnvelopeFactory::useExtractedHeader()
            );

            $transport->send($message);
            return true;
        } catch (Throwable $e) {
            Log::error('[Notify] sendByMail failed: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
}

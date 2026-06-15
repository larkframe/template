<?php

namespace App\Library;

use Genkgo\Mail;
use LarkFrame\Library;

class Notify extends Library
{

    /**
     *
     * @param $subject
     * @param $body
     * @param $toMail
     * @param $toName
     * @return bool
     */
    public function sendByMail($subject, $body, $toMail, $toName): bool
    {
        $config = $this->config['mail'];
        if (isset($config['mail_addr']) && isset($config['protocol']) && isset($config['user'])
            && isset($config['pass']) && isset($config['host'])) {
            $message = (new Mail\MessageBodyCollection($body))
                ->createMessage()
                ->withHeader(new Mail\Header\Subject($subject))
                ->withHeader(Mail\Header\From::fromEmailAddress($config['mail_addr']))
                ->withHeader(Mail\Header\To::fromSingleRecipient($toMail, $toName));

            $transport = new Mail\Transport\SmtpTransport(
                Mail\Protocol\Smtp\ClientFactory::fromString(
                    sprintf('%s://%s:%s@%s/', $config['protocol'], $config['user'],
                        $config['pass'], $config['host']))->newClient(),
                Mail\Transport\EnvelopeFactory::useExtractedHeader()
            );

            $transport->send($message);
            return true;
        }
        return false;
    }
}
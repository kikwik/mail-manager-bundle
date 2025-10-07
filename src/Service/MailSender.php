<?php

namespace Kikwik\MailManagerBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Kikwik\MailManagerBundle\Model\Log;
use Symfony\Component\Mailer\MailerInterface;

class MailSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ManagerRegistry $doctrine,
    )
    {
    }

    public function send(Log $log, bool $persistAndFlush = true)
    {
        $email = $log->getUnserializedEmail();
        if($email->getTo() || $email->getCc() || $email->getBcc()) {
            $log->setSendedAt(new \DateTimeImmutable());
            $log->setStatus(LOG::STATUS_SENT);
            $this->mailer->send($email);
        }
        if($persistAndFlush)
        {
            $this->doctrine->getManagerForClass(get_class($log))->persist($log);
            $this->doctrine->getManagerForClass(get_class($log))->flush();
        }
    }

    public function doNotSend(Log $log, bool $persistAndFlush = true)
    {
        $log->setStatus(Log::STATUS_DO_NOT_SEND);
        if($persistAndFlush)
        {
            $this->doctrine->getManagerForClass(get_class($log))->persist($log);
            $this->doctrine->getManagerForClass(get_class($log))->flush();
        }
    }

    public function needManualReview(Log $log, bool $persistAndFlush = true)
    {
        $log->setStatus(Log::STATUS_NEED_MANUAL_REVIEW);
        if($persistAndFlush)
        {
            $this->doctrine->getManagerForClass(get_class($log))->persist($log);
            $this->doctrine->getManagerForClass(get_class($log))->flush();
        }
    }
}

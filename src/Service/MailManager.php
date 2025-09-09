<?php

namespace Kikwik\MailManagerBundle\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class MailManager
{

    public function __construct(
        private readonly Mailer $mailer
    )
    {
    }


    public function send()
    {
        $email = (new Email())
            ->from('no-reply@example.com')
            ->to('davide@kikwik.it')
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');
        $this->mailer->send($email);
    }
}

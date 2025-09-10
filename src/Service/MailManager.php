<?php

namespace Kikwik\MailManagerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailManager
{

    public function __construct(
        private string $templateClass,
        private readonly EntityManagerInterface $entityManager,
        public readonly Environment $twig,
        private readonly MailerInterface $mailer,
    )
    {
    }


    public function send(Address $recipient, string $templateName, array $context = [])
    {
        $template = $this->entityManager->getRepository($this->templateClass)->findOneBy(['name' => $templateName]);
        if($template)
        {
            assert($template instanceof Template);
            if($template->isEnabled())
            {
                // create a sender object
                $sender = new Address($template->getSenderEmail(), $template->getSenderName());

                // add sender and recipient to context
                $context['sender'] = $sender;
                $context['recipient'] = $recipient;

                // render the subject
                $subjectTemplate = $this->twig->createTemplate($template->getSubject());
                $subject = $subjectTemplate->render($context);

                // render the body
                $bodyTemplate = $this->twig->createTemplate($template->getBody());
                $body = $bodyTemplate->render($context);

                // compose the email
                $email = (new Email())
                    ->from($sender)
                    ->to($recipient)
                    ->subject($subject)
                    ->html($body);

                // TODO: dispatch some event
                // TODO: save sended email

                // send the email
                $this->mailer->send($email);
            }

        }
    }
}

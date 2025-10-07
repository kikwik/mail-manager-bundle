<?php

namespace Kikwik\MailManagerBundle\Service;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Kikwik\MailManagerBundle\Model\Decorator;
use Kikwik\MailManagerBundle\Model\Log;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;


class MailBuilder
{
    private array $context = [];
    private array $recipients = [];
    private array $carbonCopies = [];
    private array $blindCarbonCopies = [];

    public function __construct(
        private Template                 $template,
        private ?Decorator               $decorator,
        private Log                      $log,
        private readonly Environment     $twig,
    )
    {
    }

    /**************************************/
    /* FLUENT INTERFACE                   */
    /**************************************/

    public function context(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function to(array $to): self
    {
        $this->recipients = array_map(fn($item) => $item instanceof Address ? $item : new Address($item), $to);
        return $this;
    }

    public function cc(array $cc): self
    {
        $this->carbonCopies = array_map(fn($item) => $item instanceof Address ? $item : new Address($item), $cc);
        return $this;
    }


    public function bcc(array $bcc): self
    {
        $this->blindCarbonCopies = array_map(fn($item) => $item instanceof Address ? $item : new Address($item), $bcc);
        return $this;
    }

    /**************************************/
    /* BUILDER                            */
    /**************************************/

    public function getLog(): Log
    {
        // render the subject
        $subjectTemplate = $this->twig->createTemplate($this->template->getSubject());
        $renderedSubject = $subjectTemplate->render($this->context);

        // render the body
        if($this->decorator)
        {
            $bodyTemplate = $this->twig->createTemplate(sprintf('%s%s%s',
                $this->decorator->getHeader(),
                $this->template->getContent(),
                $this->decorator->getFooter()
            ));
        }
        else
        {
            $bodyTemplate = $this->twig->createTemplate($this->template->getContent());
        }
        $renderedBody = $bodyTemplate->render($this->context);

        // compose the email
        $email = new TemplatedEmail();
        $email->from($this->template->getSender());
        if($this->template->getReplyToEmail())
        {
            $email->replyTo($this->template->getReplyToEmail());
        }
        foreach($this->recipients as $recipient)
        {
            $email->addTo($recipient);
        }
        foreach($this->carbonCopies as $carbonCopy)
        {
            $email->addCC($carbonCopy);
        }
        foreach($this->blindCarbonCopies as $blindCarbonCopy)
        {
            $email->addBcc($blindCarbonCopy);
        }
        $email->subject($renderedSubject);
        $email->html($renderedBody);

        // saves the email in the log and update log properties
        $this->log->fromEmail($email);

        return $this->log;
    }
}

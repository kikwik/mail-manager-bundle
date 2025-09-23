<?php

namespace Kikwik\MailManagerBundle\Service;


use Doctrine\ORM\EntityManagerInterface;
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
    private array $carbonCopies = [];
    private array $blindCarbonCopies = [];

    public function __construct(
        private Template $template,
        private ?Decorator $decorator,
        private Log $log,
        private Address $recipient,
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
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

    public function sendEmail(): self
    {
        $eamil = $this->buildEmailAndLog();
        $this->log->setSendedAt(new \DateTimeImmutable());
        $this->mailer->send($eamil);
        return $this;
    }

    public function persistLog(): self
    {
        $this->buildEmailAndLog();
        $this->entityManager->persist($this->log);
        $this->entityManager->flush();
        return $this;
    }

    /**************************************/
    /* GETTERS                            */
    /**************************************/

    public function getLog(): Log
    {
        return $this->log;
    }

    /**************************************/
    /* PRIVATE METHODS                    */
    /**************************************/
    private function buildEmailAndLog(): TemplatedEmail
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
        $email = (new TemplatedEmail())
            ->from($this->template->getSender())
            ->to($this->recipient)
            ->subject($renderedSubject)
            ->html($renderedBody);
        if($this->template->getReplyToEmail())
        {
            $email->replyTo($this->template->getReplyToEmail());
        }
        foreach($this->carbonCopies as $carbonCopy)
        {
            $email->addCC($carbonCopy);
        }
        foreach($this->blindCarbonCopies as $blindCarbonCopy)
        {
            $email->addBcc($blindCarbonCopy);
        }
        $this->log->fromEmail($email);

        return $email;
    }
}

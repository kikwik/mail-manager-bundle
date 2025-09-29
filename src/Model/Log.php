<?php

namespace Kikwik\MailManagerBundle\Model;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;

abstract class Log
{
    /**************************************/
    /* CONST                              */
    /**************************************/

    public const STATUS_READY_TO_SEND = 'READY_TO_SEND';
    public const STATUS_NEED_MANUAL_REVIEW = 'NEED_MANUAL_REVIEW';
    public const STATUS_DO_NOT_SEND = 'DO_NOT_SEND';
    public const STATUS_SENT = 'SENT';

    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    protected ?string $sender = null;

    protected ?string $recipient = null;

    protected ?string $carbonCopy = null;

    protected ?string $blindCarbonCopy = null;

    protected ?string $replyTo = null;

    protected ?string $templateName = null;

    protected ?string $subject = null;

    protected ?string $serializedEmail = null;

    protected ?string $status = self::STATUS_READY_TO_SEND;

    protected ?\DateTimeImmutable $sendedAt = null;


    /**************************************/
    /* CUSTOM METHODS                     */
    /**************************************/

    public function __toString(): string
    {
        return (string)$this->getSubject();
    }

    public function fromEmail(Email $email): static
    {
        $this
            ->setSender($email->getFrom() ? implode(', ', array_map(fn($from) => $from->toString(), $email->getFrom())) : null)
            ->setRecipient($email->getTo() ? implode(', ', array_map(fn($to) => $to->toString(), $email->getTo())) : null)
            ->setCarbonCopy($email->getCc() ? implode(', ', array_map(fn($cc) => $cc->toString(), $email->getCc())) : null)
            ->setBlindCarbonCopy($email->getBcc() ? implode(', ', array_map(fn($bcc) => $bcc->toString(), $email->getBcc())) : null)
            ->setReplyTo($email->getReplyTo() ? implode(', ', array_map(fn($replyTo) => $replyTo->toString(), $email->getReplyTo())) : null)
            ->setSubject($email->getSubject())
            ->setSerializedEmail(serialize($email));

        return $this;
    }

    public function getUnserializedEmail(): Email
    {
        return unserialize($this->serializedEmail);
    }

    /**************************************/
    /* GETTERS & SETTERS                  */
    /**************************************/


    public function getSender(): ?string
    {
        return $this->sender;
    }

    public function setSender(?string $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(?string $recipient): static
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getCarbonCopy(): ?string
    {
        return $this->carbonCopy;
    }

    public function setCarbonCopy(?string $carbonCopy): static
    {
        $this->carbonCopy = $carbonCopy;
        return $this;
    }

    public function getBlindCarbonCopy(): ?string
    {
        return $this->blindCarbonCopy;
    }

    public function setBlindCarbonCopy(?string $blindCarbonCopy): static
    {
        $this->blindCarbonCopy = $blindCarbonCopy;
        return $this;
    }

    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    public function setReplyTo(?string $replyTo): static
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function setTemplateName(?string $templateName): static
    {
        $this->templateName = $templateName;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSerializedEmail(): ?string
    {
        return $this->serializedEmail;
    }

    public function setSerializedEmail(?string $serializedEmail): static
    {
        $this->serializedEmail = $serializedEmail;
        return $this;
    }

    public function getSendedAt(): ?\DateTimeImmutable
    {
        return $this->sendedAt;
    }

    public function setSendedAt(?\DateTimeImmutable $sendedAt): static
    {
        $this->sendedAt = $sendedAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }
}

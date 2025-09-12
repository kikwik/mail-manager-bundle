<?php

namespace Kikwik\MailManagerBundle\Model;

abstract class Log implements LogInterface
{
    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    protected ?string $senderName = null;

    protected ?string $senderEmail =  null;

    protected ?string $recipientName = null;

    protected ?string $recipientEmail =  null;

    protected ?string $templateName = null;

    protected ?string $subject = null;

    protected ?string $serializedEmail = null;

    protected ?\DateTimeImmutable $sendedAt = null;

    /**************************************/
    /* CUSTOM METHODS                     */
    /**************************************/

    public function __toString(): string
    {
        return (string)$this->getSubject();
    }

    /**************************************/
    /* GETTERS & SETTERS                  */
    /**************************************/

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): static
    {
        $this->senderName = $senderName;
        return $this;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): static
    {
        $this->senderEmail = $senderEmail;
        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(?string $recipientName): static
    {
        $this->recipientName = $recipientName;
        return $this;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(?string $recipientEmail): static
    {
        $this->recipientEmail = $recipientEmail;
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



}

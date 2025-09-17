<?php

namespace Kikwik\MailManagerBundle\Model;

abstract class Log implements LogInterface
{
    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    protected ?string $sender = null;

    protected ?string $recipient = null;

    protected ?string $carbonCopy = null;

    protected ?string $blindCarbonCopy = null;

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

    public function getUnserializedEmail()
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

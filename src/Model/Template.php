<?php

namespace Kikwik\MailManagerBundle\Model;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name'])]
abstract class Template
{
    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    #[Assert\NotBlank()]
    protected ?string $name = null;

    protected bool $isEnabled = true;

    protected ?string $senderName = null;

    #[Assert\NotBlank()]
    #[Assert\Email()]
    protected ?string $senderEmail =  null;

    #[Assert\Email()]
    protected ?string $replyToEmail =  null;

    #[Assert\NotBlank()]
    protected ?string $subject = null;

    protected ?string $decoratorName = null;

    #[Assert\NotBlank()]
    protected ?string $content = null;

    /**************************************/
    /* CUSTOM METHODS                     */
    /**************************************/

    public function __toString(): string
    {
        return (string)$this->getName();
    }

    public function getSender(): Address
    {
        return new Address($this->getSenderEmail(), $this->getSenderName());
    }

    abstract public static function getTemplateChoices(): array;

    /**************************************/
    /* GETTERS & SETTERS                  */
    /**************************************/

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): static
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

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

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function setReplyToEmail(?string $replyToEmail): static
    {
        $this->replyToEmail = $replyToEmail;
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

    public function getDecoratorName(): ?string
    {
        return $this->decoratorName;
    }

    public function setDecoratorName(?string $decoratorName): static
    {
        $this->decoratorName = $decoratorName;
        return $this;
    }



    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;
        return $this;
    }

}

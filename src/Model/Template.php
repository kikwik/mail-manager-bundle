<?php

namespace Kikwik\MailManagerBundle\Model;

abstract class Template
{
    protected ?int $id = null;

    protected ?string $code = null;

    protected ?string $event = null;

    protected ?Sender $sender = null;

    protected ?string $subject = null;

    protected ?string $body = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(?string $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getSender(): ?Sender
    {
        return $this->sender;
    }

    public function setSender(?Sender $sender): static
    {
        $this->sender = $sender;
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

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;
        return $this;
    }


}

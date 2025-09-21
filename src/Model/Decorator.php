<?php

namespace Kikwik\MailManagerBundle\Model;

class Decorator
{
    /**************************************/
    /* PROPERTIES                         */
    /**************************************/

    protected ?string $name = null;

    protected ?string $header = null;

    protected ?string $footer = null;

    /**************************************/
    /* CUSTOM METHODS                     */
    /**************************************/

    public function __toString(): string
    {
        return (string)$this->getName();
    }

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

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function setHeader(?string $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function setFooter(?string $footer): static
    {
        $this->footer = $footer;
        return $this;
    }
}

<?php

namespace Kikwik\MailManagerBundle\Model;

abstract class Template
{
    public string $code;

    public string $event;

    public Sender $sender;

    public string $subject;

    public string $body;
}

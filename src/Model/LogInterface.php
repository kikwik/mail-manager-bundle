<?php

namespace Kikwik\MailManagerBundle\Model;

interface LogInterface
{
    public function getSender(): ?string;
    public function setSender(?string $sender): LogInterface;

    public function getRecipient(): ?string;
    public function setRecipient(?string $recipient): LogInterface;

    public function getCarbonCopy(): ?string;
    public function setCarbonCopy(?string $carbonCopy): LogInterface;

    public function getBlindCarbonCopy(): ?string;
    public function setBlindCarbonCopy(?string $blindCarbonCopy): LogInterface;

    public function getTemplateName(): ?string;
    public function setTemplateName(?string $templateName): LogInterface;

    public function getSubject(): ?string;
    public function setSubject(?string $subject): LogInterface;

    public function getSerializedEmail(): ?string;
    public function setSerializedEmail(?string $serializedEmail): LogInterface;

    public function getSendedAt(): ?\DateTimeImmutable;
    public function setSendedAt(?\DateTimeImmutable $sendedAt): LogInterface;

    public function __toString(): string;

}

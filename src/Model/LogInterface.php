<?php

namespace Kikwik\MailManagerBundle\Model;

interface LogInterface
{
    public function getSenderName(): ?string;

    public function setSenderName(?string $senderName): LogInterface;

    public function getSenderEmail(): ?string;

    public function setSenderEmail(?string $senderEmail): LogInterface;

    public function getRecipientName(): ?string;

    public function setRecipientName(?string $recipientName): LogInterface;

    public function getRecipientEmail(): ?string;

    public function setRecipientEmail(?string $recipientEmail): LogInterface;

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

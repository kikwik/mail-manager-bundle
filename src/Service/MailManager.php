<?php

namespace Kikwik\MailManagerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kikwik\MailManagerBundle\Model\LogInterface;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class MailManager
{

    public function __construct(
        private ?string $templateClass,
        private ?string $logClass,
        private readonly EntityManagerInterface $entityManager,
        public readonly Environment $twig,
        private readonly MailerInterface $mailer,
    )
    {
    }

    public function composeAndSend(Address $recipient, string $templateName, array $context = []): void
    {
        $this->send($this->compose($recipient, $templateName, $context));
    }

    public function compose(Address $recipient, string $templateName, array $context = [], bool $persistLog = false): ?LogInterface
    {
        if(!$this->templateClass){
            // template_class is required
            throw new \Exception('Template class not set, please define kikwik_mail_manager.template_class in config/packages/kikwik_mail_manager.yaml');
        }
        if(!$this->logClass){
            // log_class is required
            throw new \Exception('Log class not set, please define kikwik_mail_manager.log_class in config/packages/kikwik_mail_manager.yaml');
        }

        $template = $this->entityManager->getRepository($this->templateClass)->findOneBy(['name' => $templateName]);
        if($template)
        {
            assert($template instanceof Template);
            if($template->isEnabled())
            {
                // create a sender object
                $sender = new Address($template->getSenderEmail(), $template->getSenderName());

                // add sender and recipient to context
                $context['sender'] = $sender;
                $context['recipient'] = $recipient;

                // render the subject
                $subjectTemplate = $this->twig->createTemplate($template->getSubject());
                $subject = $subjectTemplate->render($context);

                // render the body
                $bodyTemplate = $this->twig->createTemplate($template->getBody());
                $body = $bodyTemplate->render($context);

                // compose the email
                $email = (new Email())
                    ->from($sender)
                    ->to($recipient)
                    ->subject($subject)
                    ->html($body);

                // TODO: dispatch some event

                // create Log for email
                /** @var LogInterface $log */
                $log = new $this->logClass();
                $log
                    ->setSender($sender->toString())
                    ->setRecipient($recipient->toString())     // TODO multiple address here
                    ->setCarbonCopy(null)           // TODO multiple address here
                    ->setBlindCarbonCopy(null)  // TODO multiple address here
                    ->setTemplateName($templateName)
                    ->setSubject($subject)
                    ->setSerializedEmail(serialize($email))
                ;
                if($persistLog)
                {
                    $this->entityManager->persist($log);
                    $this->entityManager->flush();
                }

                return $log;
            }
        }
        return null;
    }

    public function send(?LogInterface $log): void
    {
        if($log)
        {
            // retrieve the mail object
            $email = unserialize($log->getSerializedEmail());

            // TODO: dispatch some event

            // send the email
            $this->mailer->send($email);

            // save the date in the lof
            $log->setSendedAt(new \DateTimeImmutable());
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }
}

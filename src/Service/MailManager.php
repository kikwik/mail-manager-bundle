<?php

namespace Kikwik\MailManagerBundle\Service;

use App\Entity\Mail\Log;
use Doctrine\ORM\EntityManagerInterface;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailManager
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


    public function send(Address $recipient, string $templateName, array $context = [])
    {
        if(!$this->templateClass) throw new \Exception('Template class not set, please define kikwik_mail_manager.template_class in config/packages/kikwik_mail_manager.yaml');

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

                // send the email
                $this->mailer->send($email);

                if($this->logClass)
                {
                    // save sended email
                    /** @var Log $log */
                    $log = new $this->logClass();
                    $log->setSenderName($sender->getName());
                    $log->setSenderEmail($sender->getAddress());
                    $log->setRecipientName($recipient->getName());
                    $log->setRecipientEmail($recipient->getAddress());
                    $log->setTemplateName($templateName);
                    $log->setSubject($subject);
                    $log->setSerializedEmail(serialize($email));
                    $log->setSendedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($log);
                    $this->entityManager->flush();
                }

            }
        }
    }
}

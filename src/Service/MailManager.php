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

    public function composeAndSend(Address $recipient, string $templateName, array $context = [], array $carbonCopies = [], array $blindCarbonCopies = []): void
    {
        $this->send($this->compose($recipient, $templateName, $context, $carbonCopies, $blindCarbonCopies));
    }

    public function compose(Address $recipient, string $templateName, array $context = [], array $carbonCopies = [], array $blindCarbonCopies = [], bool $persistLog = false): ?LogInterface
    {
        if(!$this->templateClass){
            // template_class is required
            throw new \Exception('Template class not set, please define kikwik_mail_manager.template_class in config/packages/kikwik_mail_manager.yaml');
        }
        if(!$this->logClass){
            // log_class is required
            throw new \Exception('Log class not set, please define kikwik_mail_manager.log_class in config/packages/kikwik_mail_manager.yaml');
        }

        // find the template
        $template = $this->entityManager->getRepository($this->templateClass)->findOneBy(['name' => $templateName]);
        if($template)
        {
            assert($template instanceof Template);
            if($template->isEnabled())
            {
                // ensure that cc and bcc are array of Address objects
                $carbonCopies = array_map(fn($item) => $item instanceof Address ? $item : new Address($item), $carbonCopies);
                $blindCarbonCopies = array_map(fn($item) => $item instanceof Address ? $item : new Address($item), $blindCarbonCopies);

                // add sender and recipient to context
                $context['sender'] = $template->getSender();
                $context['recipient'] = $recipient;

                // render the subject
                $subjectTemplate = $this->twig->createTemplate($template->getSubject());
                $subject = $subjectTemplate->render($context);

                // render the body
                $bodyTemplate = $this->twig->createTemplate($template->getBody());
                $body = $bodyTemplate->render($context);

                // compose the email
                $email = (new Email())
                    ->from($template->getSender())
                    ->to($recipient)
                    ->subject($subject)
                    ->html($body);
                foreach($carbonCopies as $carbonCopy)
                {
                    $email->addCC($carbonCopy);
                }
                foreach($blindCarbonCopies as $blindCarbonCopy)
                {
                    $email->addBcc($blindCarbonCopy);
                }

                // TODO: dispatch some event

                // create Log for email
                /** @var LogInterface $log */
                $log = new $this->logClass();
                $log
                    ->setSender($template->getSender()->toString())
                    ->setRecipient($recipient->toString())
                    ->setCarbonCopy(implode(', ',array_map(fn($cc) => $cc->toString(), $carbonCopies)))
                    ->setBlindCarbonCopy(implode(', ',array_map(fn($bcc) => $bcc->toString(), $blindCarbonCopies)))
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

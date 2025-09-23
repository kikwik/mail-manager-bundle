<?php

namespace Kikwik\MailManagerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kikwik\MailManagerBundle\Model\Decorator;
use Kikwik\MailManagerBundle\Model\LogInterface;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;

/**
 * @deprecated This class is deprecated and will be removed in a future version.
 *             Please use the MailBuilderFactory or similar functionality for managing mail-related operations.
 */
final class MailManager
{

    public function __construct(
        private ?string $templateClass,
        private ?string $decoratorClass,
        private ?string $logClass,
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
    )
    {
    }

    public function composeAndSend(string $templateName, array $context, Address $recipient, array $carbonCopies = [], array $blindCarbonCopies = []): ?LogInterface
    {
        $mailLog = $this->compose($templateName, $context, $recipient, $carbonCopies, $blindCarbonCopies);
        $this->send($mailLog);
        return $mailLog;
    }

    public function composeSendAndPersist(string $templateName, array $context, Address $recipient, array $carbonCopies = [], array $blindCarbonCopies = []): ?LogInterface
    {
        $mailLog = $this->compose($templateName, $context, $recipient, $carbonCopies, $blindCarbonCopies);
        $this->send($mailLog);
        $this->persist($mailLog);
        return $mailLog;
    }

    public function compose(string $templateName, array $context, Address $recipient, array $carbonCopies = [], array $blindCarbonCopies = []): ?LogInterface
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

                // render the subject
                $subjectTemplate = $this->twig->createTemplate($template->getSubject());
                $subject = $subjectTemplate->render($context);

                // render the content
                $contentTemplate = $this->twig->createTemplate($template->getContent());
                $content = $contentTemplate->render($context);

                // decorate the content
                $decoratorTemplate = $this->twig->createTemplate('{{ content|raw }}');
                if($this->decoratorClass && $template->getDecoratorName())
                {
                    // find the decorator
                    $decorator = $this->entityManager->getRepository($this->decoratorClass)->findOneBy(['name' => $template->getDecoratorName()]);
                    if($decorator)
                    {
                        assert($decorator instanceof Decorator);
                        $decoratorTemplate = $this->twig->createTemplate(sprintf('%s{{ content|raw }}%s', $decorator->getHeader(), $decorator->getFooter()));;
                    }
                }
                $body = $decoratorTemplate->render(['content' => $content]);

                // compose the email
                $email = (new TemplatedEmail())
                    ->from($template->getSender())
                    ->to($recipient)
                    ->subject($subject)
                    ->html($body);
                if($template->getReplyToEmail())
                {
                    $email->replyTo($template->getReplyToEmail());
                }
                foreach($carbonCopies as $carbonCopy)
                {
                    $email->addCC($carbonCopy);
                }
                foreach($blindCarbonCopies as $blindCarbonCopy)
                {
                    $email->addBcc($blindCarbonCopy);
                }

                // TODO: dispatch some event to allow to modify the email object

                // create Log for email
                $logClass = $this->logClass;
                assert($logClass instanceof LogInterface);
                $log = $logClass::createFromEmail($email);
                $log->setTemplateName($templateName);

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
        }
    }

    public function persist(?LogInterface $log): void
    {
        if($log)
        {
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }
}

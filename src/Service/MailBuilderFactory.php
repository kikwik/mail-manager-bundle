<?php

namespace Kikwik\MailManagerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kikwik\MailManagerBundle\Model\Decorator;
use Kikwik\MailManagerBundle\Model\Log;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class MailBuilderFactory
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
        if(!$this->templateClass){
            // template_class is required
            throw new \Exception('Template class not set, please define kikwik_mail_manager.template_class in config/packages/kikwik_mail_manager.yaml');
        }
        if(!$this->logClass){
            // log_class is required
            throw new \Exception('Log class not set, please define kikwik_mail_manager.log_class in config/packages/kikwik_mail_manager.yaml');
        }
    }

    public function createMailBuilder(string $templateName, Address $recipient): ?MailBuilder
    {
        // find template
        $template = $this->entityManager->getRepository($this->templateClass)->findOneBy(['name' => $templateName]);
        if($template)
        {
            assert($template instanceof Template);
            if($template->isEnabled())
            {
                // eventually find decorator
                if($this->decoratorClass && $template->getDecoratorName())
                {
                    $decorator = $this->entityManager->getRepository($this->decoratorClass)->findOneBy(['name' => $template->getDecoratorName()]);
                    if($decorator)
                    {
                        assert($decorator instanceof Decorator);
                    }
                }
                else
                {
                    $decorator = null;
                }

                // create Log and set template
                $logClass = $this->logClass;
                assert($logClass instanceof Log);
                $log = new $logClass();
                $log->setTemplateName($templateName);

                // create builder
                return new MailBuilder(
                    $template, $decorator, $log, $recipient,
                    $this->twig, $this->mailer, $this->entityManager
                );
            }
        }

        return null;
    }
}



<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kikwik\MailManagerBundle\Service\MailBuilderFactory;
use Kikwik\MailManagerBundle\Service\MailSender;

return static function (ContainerConfigurator $container): void {

    $container->services()
        ->set('kikwik_mail_manager.service.mail_builder_factory', MailBuilderFactory::class)
            ->args([
                abstract_arg('Template class'),
                abstract_arg('Decorator class'),
                abstract_arg('Log class'),
                service('doctrine'),
                service('twig'),
            ])
            ->alias(MailBuilderFactory::class, 'kikwik_mail_manager.service.mail_builder_factory')

        ->set('kikwik_mail_manager.service.mail_sender', MailSender::class)
            ->args([
                service('mailer'),
                service('doctrine'),
                service('form.factory'),
            ])
            ->alias(MailSender::class, 'kikwik_mail_manager.service.mail_sender')
    ;
};

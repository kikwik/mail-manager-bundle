<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kikwik\MailManagerBundle\Service\MailBuilderFactory;

return static function (ContainerConfigurator $container): void {

    $container->services()
        ->set('kikwik_mail_manager.service.mail_builder_factory', MailBuilderFactory::class)
        ->args([
            abstract_arg('Template class'),
            abstract_arg('Decorator class'),
            abstract_arg('Log class'),
            service('doctrine.orm.entity_manager'),
            service('twig'),
            service('mailer'),
        ])
        ->alias(MailBuilderFactory::class, 'kikwik_mail_manager.service.mail_builder_factory')
    ;
};

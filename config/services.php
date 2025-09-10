<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kikwik\MailManagerBundle\Service\MailManager;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('kikwik_mail_manager.service.mail_manager', MailManager::class)
            ->args([
                abstract_arg('Template class'),
                service('doctrine.orm.entity_manager'),
                service('twig'),
                service('mailer'),
            ])
        ->alias(MailManager::class, 'kikwik_mail_manager.service.mail_manager')
    ;
};

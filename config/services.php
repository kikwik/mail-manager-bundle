<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kikwik\MailManagerBundle\Service\MailManager;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('kikwik_mail_manager.service.mail_manager', MailManager::class)
            ->args([
                service('mailer'),
            ])
        ->alias(MailManager::class, 'kikwik_mail_manager.service.mail_manager')
    ;
};

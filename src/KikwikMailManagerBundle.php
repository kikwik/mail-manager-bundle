<?php

namespace Kikwik\MailManagerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Kikwik\MailManagerBundle\Model\Decorator;
use Kikwik\MailManagerBundle\Model\Log;
use Kikwik\MailManagerBundle\Model\Template;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class KikwikMailManagerBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->stringNode('template_class')
                    ->info('The class name of your Template entity.')
                    ->example('App\Entity\Mail\MailTemplate')
                    ->defaultNull()
                    ->validate()
                        ->ifTrue(fn ($v) => !is_a($v, Template::class, true))
                        ->thenInvalid('The template_class %s must extend Kikwik\MailManagerBundle\Model\Template.')
                    ->end()
                ->end()
                ->stringNode('log_class')
                    ->info('The class name of your Log of sended email entity.')
                    ->example('App\Entity\Mail\MailLog')
                    ->defaultNull()
                    ->validate()
                        ->ifTrue(fn ($v) => !is_a($v, Log::class, true))
                        ->thenInvalid('The log_class %s must extend Kikwik\MailManagerBundle\Model\Log.')
                    ->end()
                ->end()
                ->stringNode('decorator_class')
                    ->info('The class name of your Decorator entity.')
                    ->example('App\Entity\Mail\MailDecorator')
                    ->defaultNull()
                    ->validate()
                        ->ifTrue(fn ($v) => !is_a($v, Decorator::class, true))
                        ->thenInvalid('The decorator_class %s must extend Kikwik\MailManagerBundle\Model\Decorator.')
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        // set parameter values to the MailBuilderFactory service
        $builder->getDefinition('kikwik_mail_manager.service.mail_builder_factory')
            ->setArgument(0, $config['template_class'])
            ->setArgument(1, $config['decorator_class'])
            ->setArgument(2, $config['log_class'])
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        // Load doctrine mapping for models
        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver(
            array(__DIR__ . '/../config/doctrine/mapping' => 'Kikwik\MailManagerBundle\Model'),
        ));
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$this->isAssetMapperAvailable($builder)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets' => '@kikwik/mail-manager-bundle',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php');
    }
}

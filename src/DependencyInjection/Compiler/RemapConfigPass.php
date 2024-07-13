<?php

namespace Snoke\OAuthServer\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemapConfigPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $configSelf = $container->getExtensionConfig('snoke_o_auth_server');
        $config = $container->getExtensionConfig('snoke_interface_associations');

        $container->prependExtensionConfig('snoke_interface_associations', [ 'remap' => [
                [
                    'source' => 'Snoke\OAuthServer\Interface\AuthenticatableInterface',
                    'target' => $configSelf[0]['authenticatable'],
                ],
        ]]);

        $container->loadFromExtension('snoke_interface_associations', $config[0]);

    }
}
<?php
namespace Snoke\OAuthServer\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SnokeOAuthServerExtension extends Extension
{
    public function prepend(ContainerBuilder $container)
    {
        // Füge die Konfiguration zu Doctrine hinzu
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('snoke_o_auth_server', $config);
        $container->setParameter('snoke_o_auth_server', $config);
        $container->setParameter('snoke_o_auth_server.authorize_uri', $config['authorize_uri']);
        $container->setParameter('snoke_o_auth_server.auth_code_uri', $config['auth_code_uri']);
        $container->setParameter('snoke_o_auth_server.access_token_uri', $config['access_token_uri']);
        $container->setParameter('snoke_o_auth_server.decode_token_uri', $config['decode_token_uri']);
        $container->setParameter('snoke_o_auth_server.refresh_token_uri', $config['refresh_token_uri']);
        $container->setAlias('Snoke\OAuthServer\Interface\ScopeCollectionInterface', $config['scopes']);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }
}

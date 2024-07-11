<?php
namespace Snoke\OAuthServer\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {

        $treeBuilder = new TreeBuilder('snoke_o_auth_server');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->end();

        return $treeBuilder;
    }
}

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
            ->children()
                ->scalarNode('authorize_uri')->end()
                ->scalarNode('login_uri')->end()
                ->scalarNode('authenticator')->end()
                ->scalarNode('access_token_uri')->end()
                ->scalarNode('auth_code_uri')->end()
                ->scalarNode('decode_token_uri')->end()
                ->scalarNode('refresh_token_uri')->end()
                ->scalarNode('authenticatable')->end()
                ->scalarNode('scopes')->end()
                ->arrayNode('client')
                    ->children()
                        ->arrayNode('client_id')
                            ->children()
                                ->scalarNode('length')->end()
                            ->end()
                        ->end()
                        ->arrayNode('client_secret')
                            ->children()
                                ->scalarNode('length')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('auth_code')
                    ->children()
                        ->scalarNode('invalidate_after')->end()
                        ->scalarNode('length')->end()
                    ->end()
                ->end()
                ->arrayNode('access_token')
                    ->children()
                        ->scalarNode('invalidate_after')->end()
                        ->scalarNode('length')->end()
                    ->end()
                ->end()
                ->arrayNode('refresh_token')
                    ->children()
                        ->scalarNode('invalidate_after')->end()
                        ->scalarNode('length')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

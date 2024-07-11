<?php
namespace Snoke\OAuthServer;

use Snoke\OAuthServer\DependencyInjection\Compiler\ConfigurationPass;
use Snoke\OAuthServer\DependencyInjection\Compiler\UninstallPass;
use Snoke\OAuthServer\DependencyInjection\SnokeOAuthServerExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
class SnokeOAuthServerBundle extends Bundle
{

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new DependencyInjection\SnokeOAuthServerExtension();
        }

        return $this->extension;
    }
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new UninstallPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
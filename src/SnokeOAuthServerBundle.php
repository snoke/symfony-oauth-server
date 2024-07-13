<?php
namespace Snoke\OAuthServer;

use Snoke\OAuthServer\DependencyInjection\SnokeOAuthServerExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Snoke\OAuthServer\DependencyInjection\Compiler\RemapConfigPass;
class SnokeOAuthServerBundle extends Bundle
{
    private function createRoutesFile($container) {
        $configFile = $container->getParameter('kernel.project_dir') . '/config/routes/snoke_oauth_server.yaml';

        $bundleConfigFile = __DIR__ . '/Resources/config/routes.yaml';

        if (!file_exists($configFile)) {
            $defaultConfig = file_get_contents($bundleConfigFile);
            file_put_contents($configFile, $defaultConfig);
        }
    }

    private function createPackageFile($container) {
        $configFile = $container->getParameter('kernel.project_dir') . '/config/packages/snoke_o_auth_server.yaml';

        $bundleConfigFile = __DIR__ . '/Resources/config/snoke_o_auth_server.yaml';

        if (!file_exists($configFile)) {
            $defaultConfig = file_get_contents($bundleConfigFile);
            file_put_contents($configFile, $defaultConfig);
        }
    }

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

        $this->createRoutesFile($container);
        $this->createPackageFile($container);

        parent::build($container);
        $container->addCompilerPass(new RemapConfigPass());
    }
}
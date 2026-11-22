<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\Compiler\UniqueIdentifierPass;

final class TmonierSyliusPlatformHealthPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new UniqueIdentifierPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}

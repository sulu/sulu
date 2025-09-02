<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Application;

use Sulu\Bundle\PreviewBundle\SuluPreviewBundle;
use Sulu\Bundle\RouteBundle\SuluRouteBundle as DeprecatedSuluRouteBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Sulu\Route\Infrastructure\Symfony\HttpKernel\SuluRouteBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * AppKernel for functional tests.
 */
class Kernel extends SuluTestKernel implements CompilerPassInterface
{
    /**
     * @var string|null
     */
    private $config = 'default';

    public function __construct(string $environment, bool $debug, string $suluContext = SuluKernel::CONTEXT_ADMIN)
    {
        $environmentParts = \explode('_', $environment, 2);
        $environment = $environmentParts[0];
        $this->config = $environmentParts[1] ?? $this->config;

        parent::__construct($environment, $debug, $suluContext);
    }

    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('sulu_route.route_admin')
            ->setPublic(true);
    }

    public function registerBundles(): iterable
    {
        $bundles = [...parent::registerBundles()];

        $hasDynamicRouting = false;
        foreach ($bundles as $key => $bundle) {
            // remove old route bundle to avoid conflicts
            if (DeprecatedSuluRouteBundle::class === $bundle::class
                || SuluPreviewBundle::class === $bundle::class
                || \str_contains($bundle::class, 'Sulu')
                || \str_contains($bundle::class, 'Massive')
                || \str_contains($bundle::class, 'PHPCR')
                || \str_contains($bundle::class, 'SecurityBundle')
            ) {
                unset($bundles[$key]);
            }
            // remove old route bundle to avoid conflicts
            if (CmfRoutingBundle::class === $bundle::class) {
                $hasDynamicRouting = true;
            }
        }

        if (!$hasDynamicRouting) {
            $bundles[] = new CmfRoutingBundle();
        }

        $bundles[] = new SuluRouteBundle();

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/config.yml');

        if (\file_exists(__DIR__ . '/config/config_' . $this->config . '.yml')) {
            $loader->load(__DIR__ . '/config/config_' . $this->config . '.yml');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        return $parameters;
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        if (\str_starts_with($request->getPathInfo(), '/en/')) { // use for the CmfRouteProviderTest
            $request->attributes->set(RequestAttributeEnum::SITE->value, 'sulu-io');
            $request->attributes->set(RequestAttributeEnum::SLUG->value, \substr($request->getPathInfo(), 3));
        }

        return parent::handle($request, $type, $catch);
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir() . '/' . $this->config;
    }

    public function getCommonCacheDir(): string
    {
        return parent::getCommonCacheDir() . '/' . $this->config;
    }
}

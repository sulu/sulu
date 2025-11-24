<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Tests\Application;

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * AppKernel for functional tests.
 */
class Kernel extends SuluTestKernel
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

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(__DIR__ . '/config/config.yml');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        return $parameters;
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir() . '/' . $this->config;
    }

    public function getShareDir(): string
    {
        return parent::getShareDir() . '/' . $this->config;
    }
}

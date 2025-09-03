<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application;

use Sulu\Bundle\AudienceTargetingBundle\SuluAudienceTargetingBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Sulu\Content\Tests\Application\ExampleTestBundle\ExampleTestBundle;
use Sulu\Snippet\Infrastructure\Symfony\HttpKernel\SuluSnippetBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SuluTestKernel
{
    public function registerBundles(): iterable
    {
        $bundles = [...parent::registerBundles()];
        $bundles[] = new ExampleTestBundle();
        $bundles[] = new SuluSnippetBundle();

        foreach ($bundles as $key => $bundle) {
            // Audience Targeting is not configured and so should not be here
            // remove deprecated SuluSnippetBundle to avoid conflicts
            if (
                $bundle instanceof SuluAudienceTargetingBundle
            ) {
                unset($bundles[$key]);
            }
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        $loader->load(__DIR__ . '/config/config_' . $this->getContext() . '.yml');

        $devConfigFile = __DIR__ . '/config/config_' . $this->getContext() . '_' . $this->getEnvironment() . '.yml';
        if (\is_file($devConfigFile)) {
            $loader->load($devConfigFile);
        }
    }

    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        $gedmoReflection = new \ReflectionClass(\Gedmo\Exception::class);
        $parameters['gedmo_directory'] = \dirname($gedmoReflection->getFileName());

        return $parameters;
    }
}

// Needed for preview PreviewKernelFactory
\class_alias(Kernel::class, 'App\\Kernel');

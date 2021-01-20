<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\PropertiesProvider;

use Symfony\Component\HttpFoundation\File\File;

class MediaPropertiesProvider implements MediaPropertiesProviderInterface
{
    /**
     * @var iterable<PropertiesProviderInterface>
     */
    private $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function provide(File $file): array
    {
        $properties = [];

        foreach ($this->providers as $provider) {
            if (!$provider::supports($file)) {
                continue;
            }

            $properties = \array_merge(
                $properties,
                $provider->provide($file)
            );
        }

        return $properties;
    }
}

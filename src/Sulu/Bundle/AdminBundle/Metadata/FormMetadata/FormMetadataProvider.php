<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

/**
 * @internal This class should not be extended or initialized by any application outside of sulu.
 *           You can inject custom loaders or visitors to adjust the behaviour of the service in your project.
 */
class FormMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var array<string, MetadataInterface>
     */
    private array $cache = [];

    /**
     * @param iterable<FormMetadataLoaderInterface> $formMetadataLoaders
     * @param iterable<FormMetadataVisitorInterface> $formMetadataVisitors
     * @param iterable<TypedFormMetadataVisitorInterface> $typedFormMetadataVisitors
     */
    public function __construct(
        private iterable $formMetadataLoaders,
        private iterable $formMetadataVisitors,
        private iterable $typedFormMetadataVisitors,
        private string $fallbackLocale,
    ) {
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): MetadataInterface
    {
        $cacheKey = $key . '.' . $locale . '.' . \serialize($metadataOptions);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $formMetadata = null;
        foreach ($this->formMetadataLoaders as $metadataLoader) {
            $formMetadata = $metadataLoader->getMetadata($key, $locale, $metadataOptions);
            if ($formMetadata) {
                break;
            }
        }

        if (!$formMetadata) {
            foreach ($this->formMetadataLoaders as $metadataLoader) {
                $formMetadata = $metadataLoader->getMetadata($key, $this->fallbackLocale, $metadataOptions);
                if ($formMetadata) {
                    break;
                }
            }
        }

        if (!$formMetadata) {
            throw new MetadataNotFoundException('form', $key);
        }

        if ($formMetadata instanceof FormMetadata) {
            foreach ($this->formMetadataVisitors as $formMetadataVisitor) {
                $formMetadataVisitor->visitFormMetadata($formMetadata, $locale, $metadataOptions);
            }
        } elseif ($formMetadata instanceof TypedFormMetadata) {
            foreach ($this->typedFormMetadataVisitors as $typedFormMetadataVisitor) {
                $typedFormMetadataVisitor->visitTypedFormMetadata($formMetadata, $key, $locale, $metadataOptions);
            }
        }

        return $this->cache[$cacheKey] = $formMetadata;
    }
}

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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver;

use Psr\Log\LoggerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverMetadataAwareInterface;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class ImageMapPropertyResolver implements PropertyResolverMetadataAwareInterface
{
    private MetadataResolver $metadataResolver;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MetadataProviderRegistry $metadataProviderRegistry,
        private readonly bool $debug = false,
    ) {
    }

    /**
     * @internal
     *
     * Prevent circular dependency by injecting the MetadataResolver after instantiation
     */
    public function setMetadataResolver(MetadataResolver $metadataResolver): void
    {
        $this->metadataResolver = $metadataResolver;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function resolve(mixed $data, string $locale, array $params = [], ?FieldMetadata $metadata = null): ContentView
    {
        $hotspots = (\is_array($data) && isset($data['hotspots']) && \is_array($data['hotspots'])) && \array_is_list($data['hotspots'])
            ? $data['hotspots']
            : [];

        if (null === $metadata) {
            throw new \InvalidArgumentException('Metadata must be provided for block resolving.');
        }

        $hotspots = [] !== $hotspots ? $this->resolveHotspots($hotspots, $locale, $metadata) : ContentView::create([], []);

        $returnedParams = $params;

        if (!\is_array($data)
            || !isset($data['imageId'])
            || !\is_numeric($data['imageId'])
        ) {
            return ContentView::create([
                'image' => null,
                'hotspots' => $hotspots,
            ], [
                'imageId' => null,
                ...$returnedParams,
            ]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? MediaResourceLoader::getKey();
        $imageId = (int) $data['imageId'];

        return ContentView::create(
            [
                'image' => new ResolvableResource($imageId, $resourceLoaderKey, -50),
                'hotspots' => $hotspots,
            ],
            [
                'imageId' => $imageId,
                ...$returnedParams,
            ],
        );
    }

    /**
     * @param non-empty-list<mixed> $hotspots
     */
    private function resolveHotspots(array $hotspots, string $locale, FieldMetadata $metadata): ContentView
    {
        $metadataTypes = $metadata->getTypes();

        $typedFormMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata('block', $locale, []);

        \assert($typedFormMetadata instanceof TypedFormMetadata, 'Block form metadata not found for image map resolving.');

        $globalBlocksMetadata = $typedFormMetadata->getForms();
        $innerContentViews = [];
        foreach ($hotspots as $key => $block) {
            if (!\is_array($block) || !isset($block['type']) || !\is_string($block['type'])) {
                continue;
            }
            if (!isset($block['hotspot']) || !\is_array($block['hotspot'])) {
                continue;
            }

            $type = $block['type'];
            $formMetadata = $metadataTypes[$type] ?? null;

            if (!$formMetadata instanceof FormMetadata) {
                $errorMessage = \sprintf(
                    'Metadata type "%s" in "%s" not found, founded types are: "%s"',
                    $type,
                    $metadata->getName(),
                    \implode('", "', \array_keys($metadataTypes)),
                );

                $this->logger->error($errorMessage);

                if ($this->debug) {
                    throw new \UnexpectedValueException($errorMessage);
                }

                $type = $metadata->getDefaultType();
                $formMetadata = $metadataTypes[$type] ?? null;
                if (!$formMetadata instanceof FormMetadata) {
                    continue;
                }
            }

            $globalBlockType = $this->getGlobalBlockType($formMetadata);
            if ($globalBlockType && \array_key_exists($globalBlockType, $globalBlocksMetadata)) {
                $formMetadata = $globalBlocksMetadata[$globalBlockType];
            }

            $innerContentViews[$key] = ContentView::create(
                \array_merge(
                    [
                        'type' => $type,
                        'hotspot' => $block['hotspot'],
                    ],
                    $this->metadataResolver->resolveItems($formMetadata->getItems(), $block, $locale)
                ),
                []
            );
        }

        return ContentView::create(\array_values($innerContentViews), []);
    }

    private function getGlobalBlockType(FormMetadata $fieldMetadata): ?string
    {
        $tag = $fieldMetadata->getTagsByName('sulu.global_block')[0] ?? null;

        /** @var string|null $result */
        $result = $tag?->getAttribute('global_block');

        return $result;
    }

    public static function getType(): string
    {
        return 'image_map';
    }
}

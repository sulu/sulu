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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Psr\Log\LoggerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\BlockVisitor\BlockVisitorInterface;

class BlockPropertyResolver implements PropertyResolverMetadataAwareInterface
{
    private MetadataResolver $metadataResolver;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MetadataProviderRegistry $metadataProviderRegistry,
        /** @var iterable<BlockVisitorInterface> */
        private readonly iterable $blockVisitors,
        private readonly bool $debug = false,
    ) {
    }

    /**
     * Prevent circular dependency by injecting the MetadataResolver after instantiation.
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
        $returnedParams = $params;

        if (!\is_array($data) || !\array_is_list($data)) {
            return ContentView::create([], [...$returnedParams]);
        }

        if (null === $metadata) {
            throw new \InvalidArgumentException('Metadata must be provided for block resolving.');
        }

        $metadataTypes = $metadata->getTypes();

        $typedFormMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata('block', $locale, []);

        \assert($typedFormMetadata instanceof TypedFormMetadata, 'Block form metadata not found for block resolving.');

        $globalBlocksMetadata = $typedFormMetadata->getForms();

        $contentViews = [];
        /** @var array<string, mixed> $block */
        foreach ($data as $key => $block) {
            if (!\is_array($block) || !isset($block['type']) || !\is_string($block['type'])) {
                continue;
            }

            foreach ($this->blockVisitors as $blockVisitor) {
                $block = $blockVisitor->visit($block);

                if (null === $block) {
                    continue 2;
                }
            }

            /** @var string $type */
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

            $contentViews[$key] = ContentView::create(
                \array_merge(
                    ['type' => $type],
                    $this->metadataResolver->resolveItems($formMetadata->getItems(), $block, $locale)
                ),
                [
                    ...$returnedParams,
                ]
            );
        }

        $minOccurs = $metadata->getMinOccurs();
        $maxOccurs = $metadata->getMaxOccurs();

        if (1 === $minOccurs && 1 === $maxOccurs && \count($contentViews) > 0) {
            return $contentViews[0];
        }

        return ContentView::create($contentViews, [...$returnedParams]);
    }

    private function getGlobalBlockType(FormMetadata $formMetadata): ?string
    {
        $tag = $formMetadata->getTagsByName('sulu.global_block')[0] ?? null;

        /** @var string|null $result */
        $result = $tag?->getAttribute('global_block');

        return $result;
    }

    public static function getType(): string
    {
        return 'block';
    }
}

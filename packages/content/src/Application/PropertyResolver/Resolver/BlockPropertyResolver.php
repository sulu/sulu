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
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\GlobalBlockMetadataResolver;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\BlockVisitor\BlockVisitorInterface;

class BlockPropertyResolver implements PropertyResolverMetadataAwareInterface
{
    private MetadataResolver $metadataResolver;

    private GlobalBlockMetadataResolver $globalBlockMetadataResolver;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MetadataProviderRegistry $metadataProviderRegistry,
        /** @var iterable<BlockVisitorInterface> */
        private readonly iterable $blockVisitors,
        private readonly bool $debug = false,
    ) {
        $this->globalBlockMetadataResolver = new GlobalBlockMetadataResolver($metadataProviderRegistry);
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

            $formMetadata = $this->globalBlockMetadataResolver->resolve($formMetadata, $locale);

            $blockDefaults = ['type' => $type];
            $blockIdGeneratorOption = $metadata->findOption('block_id_generator');
            if ($blockIdGeneratorOption && true === $blockIdGeneratorOption->getValue() && isset($block['_id'])) {
                $blockDefaults['_id'] = $block['_id'];
            }

            $resolvedBlock = \array_merge(
                $blockDefaults,
                $this->metadataResolver->resolveItems($formMetadata->getItems(), $block, $locale),
            );

            $settingsFormKeyOption = $metadata->findOption('settings_form_key');
            if ($settingsFormKeyOption) {
                $settingsFormKey = $settingsFormKeyOption->getValue();
                if (\is_string($settingsFormKey) && isset($block['settings'])) {
                    $resolvedBlock['settings'] = $this->resolveBlockSettings($block, $settingsFormKey, $locale);
                }
            }

            $contentViews[$key] = ContentView::create($resolvedBlock, [...$returnedParams]);
        }

        $minOccurs = $metadata->getMinOccurs();
        $maxOccurs = $metadata->getMaxOccurs();

        if (1 === $minOccurs && 1 === $maxOccurs && \count($contentViews) > 0) {
            return $contentViews[0];
        }

        return ContentView::create($contentViews, [...$returnedParams]);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function resolveBlockSettings(array $block, string $settingsFormKey, string $locale): ContentView
    {
        if (!isset($block['settings']) || !\is_array($block['settings'])) {
            return ContentView::create([], []);
        }

        $settingsData = $block['settings'];
        $formMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($settingsFormKey, $locale, []);

        if (!$formMetadata instanceof FormMetadata) {
            return ContentView::create($settingsData, []);
        }

        return ContentView::create(
            $this->metadataResolver->resolveItems($formMetadata->getItems(), $settingsData, $locale),
            [],
        );
    }

    public static function getType(): string
    {
        return 'block';
    }
}

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

namespace Sulu\Content\Application\ContentWorkflow\Subscriber;

use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Exception\ShadowSourceNotPublishedException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class PublishTransitionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContentCopierInterface $contentCopier,
        private ContentAggregatorInterface $contentAggregator,
    ) {
    }

    public function onPublish(TransitionEvent $transitionEvent): void
    {
        $dimensionContent = $transitionEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        $context = $transitionEvent->getContext();

        $dimensionContentCollection = $context[ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY] ?? null;
        $dimensionAttributes = $context[ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY] ?? null;
        $contentRichEntity = $context[ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY] ?? null;

        if (!\is_array($dimensionAttributes)) {
            throw new \RuntimeException('No "dimensionAttributes" given.');
        }

        if (!$dimensionContentCollection instanceof DimensionContentCollectionInterface) {
            throw new \RuntimeException('No "dimensionContentCollection" given.');
        }

        if (!$contentRichEntity instanceof ContentRichEntityInterface) {
            throw new \RuntimeException('No "contentRichEntity" given.');
        }

        $sourceDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = DimensionContentInterface::STAGE_LIVE;

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        if ($dimensionContent instanceof WorkflowInterface) {
            if (!$dimensionContent->getWorkflowPublished()) {
                $dimensionContent->setWorkflowPublished(new \DateTimeImmutable());
            }
        }

        $shadowLocale = $dimensionContent instanceof ShadowInterface
            ? $dimensionContent->getShadowLocale()
            : null;

        if (!$shadowLocale) {
            $publishedDimensionContent = $this->contentCopier->copyFromDimensionContentCollection(
                $dimensionContentCollection,
                $contentRichEntity,
                $targetDimensionAttributes
            );

            if (!$publishedDimensionContent instanceof ShadowInterface) {
                $this->createVersion($dimensionContentCollection, $contentRichEntity, $dimensionAttributes, $locale);

                return;
            }

            $shadowLocales = $publishedDimensionContent->getShadowLocalesForLocale($locale);

            foreach ($shadowLocales as $shadowLocale) {
                $targetDimensionAttributes['locale'] = $shadowLocale;

                $this->contentCopier->copyFromDimensionContentCollection(
                    $dimensionContentCollection,
                    $contentRichEntity,
                    $targetDimensionAttributes,
                    [
                        'ignoredAttributes' => [
                            'shadowOn',
                            'shadowLocale',
                            'url',
                        ],
                    ]
                );
            }

            $this->createVersion($dimensionContentCollection, $contentRichEntity, $dimensionAttributes, $locale);

            return;
        }

        $sourceDimensionAttributes['locale'] = $shadowLocale;
        $sourceDimensionAttributes['stage'] = DimensionContentInterface::STAGE_LIVE;

        // Resolve the source content up front to fail with a translatable error when its locale is
        // not published yet, instead of crashing later in the copy.
        try {
            $sourceDimensionContent = $this->contentAggregator->aggregate($contentRichEntity, $sourceDimensionAttributes);
        } catch (ContentNotFoundException $exception) {
            throw new ShadowSourceNotPublishedException($locale, $shadowLocale, $exception);
        }

        if ($sourceDimensionContent instanceof TemplateInterface && null === $sourceDimensionContent->getTemplateKey()) {
            throw new ShadowSourceNotPublishedException($locale, $shadowLocale);
        }

        $data = [
            // @see \Sulu\Content\Application\ContentDataMapper\DataMapper\ShadowDataMapper::map
            'shadowOn' => true,
            'shadowLocale' => $shadowLocale,
        ];

        // Preserve the shadow locale's own route slug so the copy from the source locale
        // does not overwrite the shadow locale's Route entity with the source locale's url.
        if ($dimensionContent instanceof RoutableInterface) {
            $route = $dimensionContent->getRoute();
            if ($route instanceof Route) {
                $data['url'] = $route->getSlug();
            }
        }

        $this->contentCopier->copyFromDimensionContent(
            $sourceDimensionContent,
            $contentRichEntity,
            $targetDimensionAttributes,
            ['data' => $data]
        );

        $this->createVersion($dimensionContentCollection, $contentRichEntity, $dimensionAttributes, $locale);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param DimensionContentCollectionInterface<T> $dimensionContentCollection
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     */
    private function createVersion(
        DimensionContentCollectionInterface $dimensionContentCollection,
        ContentRichEntityInterface $contentRichEntity,
        array $dimensionAttributes,
        string $locale,
    ): void {
        $this->contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection,
            $contentRichEntity,
            \array_merge($dimensionAttributes, ['locale' => $locale, 'version' => \time()]),
            ['ignoredAttributes' => ['url']] // ignore url, because we cannot restore it from a version
        );
    }

    public static function getSubscribedEvents(): array
    {
        $eventName = 'workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH;

        return [
            $eventName => 'onPublish',
        ];
    }
}

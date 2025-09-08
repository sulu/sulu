<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Sulu\Reference;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\OnClearEventArgs;
use Sulu\Bundle\ReferenceBundle\Application\Message\RefreshReferenceMessage;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ResetInterface;

class ReferenceDoctrineEventListener implements ResetInterface
{
    use HandleTrait;

    /**
     * @var array<DimensionContentInterface<ContentRichEntityInterface>>
     */
    private array $dimensionContents = [];  // @phpstan-ignore-line missingType.generics

    /**
     * @var array<DimensionContentInterface<ContentRichEntityInterface>>
     */
    private array $removedDimensionContents = [];  // @phpstan-ignore-line missingType.generics

    /**
     * @var array<array{resourceKey: string, resourceId: string}>
     */
    private array $removedContentRichEntityIds = [];

    public function __construct(
        private MessageBusInterface $messageBus, // @phpstan-ignore-line property.onlyWritten (the HandleTrait is using it)
        private ReferenceRepositoryInterface $referenceRepository
    ) {
    }

    public function prePersist(LifecycleEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $object = $args->getObject();
        if (!$object instanceof DimensionContentInterface || DimensionContentInterface::CURRENT_VERSION !== $object->getVersion()) {
            return;
        }

        $this->dimensionContents[] = $object;
    }

    public function preUpdate(PreUpdateEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $object = $args->getObject();
        if (!$object instanceof DimensionContentInterface || DimensionContentInterface::CURRENT_VERSION !== $object->getVersion()) {
            return;
        }

        $this->dimensionContents[] = $object;
    }

    public function preRemove(LifecycleEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $object = $args->getObject();

        if ($object instanceof DimensionContentInterface) {
            // We only remove references for the current version of a DimensionContent
            if (DimensionContentInterface::CURRENT_VERSION !== $object->getVersion()) {
                return;
            }

            $this->removedDimensionContents[] = $object;
        } elseif ($object instanceof ContentRichEntityInterface) {
            // TODO is there a better way to get the ResourceKey of the ContentRichEntity?
            $dimensionContentClass = $this->getDimensionContentClass($object);
            $this->removedContentRichEntityIds[] = [
                'resourceKey' => $dimensionContentClass::getResourceKey(),
                'resourceId' => (string) $object->getId(),
            ];
        }
    }

    public function onClear(OnClearEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $this->reset();
    }

    public function postFlush(PostFlushEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $dimensionContents = $this->dimensionContents;
        $removedDimensionContents = $this->removedDimensionContents;
        $removedContentRichEntityIds = $this->removedContentRichEntityIds;

        // reset here to avoid infinite loop due to flushes in the handler
        $this->reset();

        foreach ($dimensionContents as $dimensionContent) {
            $resource = $dimensionContent->getResource();
            $locale = $dimensionContent->getLocale();
            $resourceId = $resource->getId();

            // Skip dimension content that doesn't have required values
            if (null === $locale) {
                continue;
            }

            $this->handle(
                new RefreshReferenceMessage(
                    $dimensionContent::getResourceKey(),
                    (string) $resourceId,
                    $locale,
                    $dimensionContent->getStage()
                )
            );
        }

        foreach ($removedDimensionContents as $dimensionContent) {
            $resource = $dimensionContent->getResource();
            $locale = $dimensionContent->getLocale();
            $resourceId = $resource->getId();

            // Skip dimension content that doesn't have required values
            if (null === $locale) {
                continue;
            }

            $this->referenceRepository->removeBy([
                'referenceResourceKey' => $dimensionContent::getResourceKey(),
                'referenceResourceId' => (string) $resourceId,
                'referenceLocale' => $locale,
                'referenceContext' => $dimensionContent->getStage(),
            ]);
        }

        foreach ($removedContentRichEntityIds as $entityInfo) {
            // Remove ALL references for this entity (all locales, all contexts)
            $this->referenceRepository->removeBy([
                'referenceResourceKey' => $entityInfo['resourceKey'],
                'referenceResourceId' => $entityInfo['resourceId'],
            ]);
        }
    }

    public function reset(): void
    {
        $this->dimensionContents = [];
        $this->removedDimensionContents = [];
        $this->removedContentRichEntityIds = [];
    }

    /**
     * @param ContentRichEntityInterface<DimensionContentInterface> $entity
     *
     * @return class-string<DimensionContentInterface<ContentRichEntityInterface>>
     */
    private function getDimensionContentClass(ContentRichEntityInterface $entity): string // @phpstan-ignore-line missingType.generics
    {
        $dimensionContent = $entity->createDimensionContent();

        return $dimensionContent::class;
    }
}

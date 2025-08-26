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

    public function __construct(private MessageBusInterface $messageBus) // @phpstan-ignore-line property.onlyWritten (the HandleTrait is using it)
    {
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
        if (!$object instanceof DimensionContentInterface || DimensionContentInterface::CURRENT_VERSION !== $object->getVersion()) {
            return;
        }

        foreach ($this->dimensionContents as $key => $content) {
            if ($content === $object) {
                unset($this->dimensionContents[$key]);
            }
        }
    }

    public function onClear(OnClearEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $this->reset();
    }

    public function postFlush(PostFlushEventArgs $args): void // @phpstan-ignore-line missingType.generics
    {
        $dimensionContents = $this->dimensionContents;

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

        $this->reset();
    }

    public function reset(): void
    {
        $this->dimensionContents = [];
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\EventSubscriber;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal This class should not be extended or initialized by any application outside of sulu.
 *           You can create your own response listener to change the behaviour or use Symfony
 *           dependency injection container to replace this service.
 */
class TagsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReferenceStoreInterface $referenceStore,
        private SymfonyResponseTagger $symfonyResponseTagger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['addTags', 1024],
        ];
    }

    public function addTags(): void
    {
        $tags = $this->referenceStore->getAll();

        if (\count($tags) <= 0) {
            return;
        }

        $this->symfonyResponseTagger->addTags($tags);
    }
}

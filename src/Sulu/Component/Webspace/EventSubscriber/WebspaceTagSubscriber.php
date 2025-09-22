<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\EventSubscriber;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal This class should not be extended or initialized by any application outside of sulu.
 *           You can create your own response listener to change the behaviour or use Symfony
 *           dependency injection container to replace this service.
 */
class WebspaceTagSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReferenceStoreInterface $referenceStore,
        private RequestAnalyzerInterface $requestAnalyzer
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['addWebspaceTag', 2048], // Priority needs to be higher than the TagsSubscriber (1024)
        ];
    }

    public function addWebspaceTag(): void
    {
        /** @var Webspace|null $webspace */
        $webspace = $this->requestAnalyzer->getWebspace();
        if (!$webspace) {
            return;
        }

        $webspaceKey = $webspace->getKey();
        $this->referenceStore->add($webspaceKey, 'webspace');
    }
}

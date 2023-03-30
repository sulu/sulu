<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebspaceSubscriber implements EventSubscriberInterface
{
    private \Sulu\Component\DocumentManager\PropertyEncoder $propertyEncoder;

    private \Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector $documentInspector;

    private \Sulu\Component\DocumentManager\DocumentManagerInterface $documentManager;

    private \Sulu\Component\Webspace\Manager\WebspaceManagerInterface $webspaceManager;

    public function __construct(
        PropertyEncoder $propertyEncoder,
        DocumentInspector $documentInspector,
        DocumentManagerInterface $documentManager,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->propertyEncoder = $propertyEncoder;
        $this->documentInspector = $documentInspector;
        $this->documentManager = $documentManager;
        $this->webspaceManager = $webspaceManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::COPY => ['deleteUnavailableLocales', 256],
            Events::PERSIST => ['handleWebspace'],
            // should happen after content is hydrated
            Events::HYDRATE => ['handleWebspace', -10],
        ];
    }

    public function handleWebspace(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof WebspaceBehavior) {
            return;
        }

        $webspaceName = $this->documentInspector->getWebspace($document);
        $event->getAccessor()->set('webspaceName', $webspaceName);
    }

    public function deleteUnavailableLocales(CopyEvent $event)
    {
        $copiedNode = $event->getCopiedNode();
        $copiedDocument = $this->documentManager->find(
            $event->getCopiedPath(),
            $this->documentInspector->getLocale($event->getDocument())
        );

        $webspace = $this->webspaceManager->findWebspaceByKey($this->documentInspector->getWebspace($copiedDocument));
        if (!$webspace) {
            return;
        }

        $webspaceLocales = \array_map(function($localization) {
            return $localization->getLocale();
        }, $webspace->getAllLocalizations());
        $documentLocales = $this->documentInspector->getLocales($copiedDocument);

        foreach ($documentLocales as $documentLocale) {
            if (\in_array($documentLocale, $webspaceLocales)) {
                continue;
            }

            $localizedProperties = $copiedNode->getProperties(
                $this->propertyEncoder->localizedContentName('*', $documentLocale)
            );

            foreach ($localizedProperties as $localizedProperty) {
                $localizedProperty->remove();
            }
        }
    }
}

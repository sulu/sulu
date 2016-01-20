<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document\Subscriber;

use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PathBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events for custom-urls.
 */
class CustomUrlSubscriber implements EventSubscriberInterface
{
    /**
     * @var GeneratorInterface
     */
    private $generator;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    public function __construct(
        GeneratorInterface $generator,
        DocumentManagerInterface $documentManager,
        PathBuilder $pathBuilder
    )
    {
        $this->generator = $generator;
        $this->documentManager = $documentManager;
        $this->pathBuilder = $pathBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [Events::PERSIST => 'handlePersist'];
    }

    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!($document instanceof CustomUrlBehavior)) {
            return;
        }

        // TODO if history exists link them to new nodes
        // TODO localization if multilingual is true

        $domains = $this->generator->generate($document->getBaseDomain(), $document->getDomainParts());

        foreach ($domains as $domain) {
            /** @var RouteDocument $routeDocument */
            $routeDocument = $this->documentManager->create('route');
            $routeDocument->setTargetDocument($document);

            $this->documentManager->persist(
                $routeDocument,
                $event->getLocale(),
                [
                    'path' => sprintf('%s/%s', $this->getRoutesPath('sulu_io'), $domain),
                    'auto_create' => true,
                ]
            );
        }
    }

    private function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom-urls%', '%custom-urls-routes%']);
    }
}

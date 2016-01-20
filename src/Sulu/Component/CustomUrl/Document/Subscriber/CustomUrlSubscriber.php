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

use PHPCR\Util\PathHelper;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
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

    /**
     * @var DocumentInspector
     */
    protected $inspector;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(
        GeneratorInterface $generator,
        DocumentManagerInterface $documentManager,
        PathBuilder $pathBuilder,
        DocumentInspector $inspector,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->generator = $generator;
        $this->documentManager = $documentManager;
        $this->pathBuilder = $pathBuilder;
        $this->inspector = $inspector;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [Events::PERSIST => 'handlePersist', Events::HYDRATE => 'handleHydrate'];
    }

    /**
     * Creates routes for persisted custom-url.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!($document instanceof CustomUrlBehavior)) {
            return;
        }

        // TODO if history exists link them to new nodes

        $webspaceKey = $this->inspector->getWebspace($document);
        $locales = null;
        if ($document->isMultilingual()) {
            $locales = $this->webspaceManager->findWebspaceByKey($webspaceKey)->getAllLocalizations();
        }

        foreach ($locales as $locale) {
            $domain = $this->generator->generate($document->getBaseDomain(), $document->getDomainParts(), $locale);

            /** @var RouteDocument $routeDocument */
            $routeDocument = $this->documentManager->create('route');
            $routeDocument->setTargetDocument($document);
            $routeDocument->setLocale($locale->getLocalization());

            $this->documentManager->persist(
                $routeDocument,
                $event->getLocale(),
                [
                    'path' => sprintf('%s/%s', $this->getRoutesPath($webspaceKey), $domain),
                    'auto_create' => true,
                ]
            );
        }
    }

    /**
     * Set routes to custom-url.
     *
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!($document instanceof CustomUrlBehavior)) {
            return;
        }

        $webspaceKey = $this->inspector->getWebspace($document);
        $routes = [];
        $referrers = $this->inspector->getReferrers($document);
        foreach ($referrers as $routeDocument) {
            if ($routeDocument instanceof RouteDocument) {
                $routes[$routeDocument->getLocale()] = PathHelper::relativizePath(
                    $routeDocument->getPath(),
                    $this->getRoutesPath($webspaceKey)
                );
            }
        }

        $document->setRoutes($routes);
    }

    /**
     * Return routes path for custom-url in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    private function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom-urls%', '%custom-urls-routes%']);
    }
}

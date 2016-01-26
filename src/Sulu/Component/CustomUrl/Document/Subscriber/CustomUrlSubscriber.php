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
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Localization\Localization;
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

        $oldRoutes = $document->getRoutes();

        $webspaceKey = $this->inspector->getWebspace($document);
        $domain = $this->generator->generate($document->getBaseDomain(), $document->getDomainParts());
        $locale = $this->webspaceManager->findWebspaceByKey($webspaceKey)->getLocalization(
            $document->getTargetLocale()
        );
        $route = $this->createDomain(
            $domain,
            $document,
            $locale,
            $event->getLocale(),
            $this->getRoutesPath($webspaceKey)
        );

        foreach ($oldRoutes as $oldRoute) {
            $oldRoute->setTargetDocument($route);
            $oldRoute->setHistory(true);
            $this->documentManager->persist(
                $oldRoute,
                $event->getLocale(),
                [
                    'path' => $oldRoute->getPath(),
                    'auto_create' => true,
                ]
            );
        }
    }

    private function createDomain(
        $domain,
        CustomUrlBehavior $document,
        Localization $locale,
        $persistedLocale,
        $routesPath
    ) {
        $path = sprintf('%s/%s', $routesPath, $domain);
        $routeDocument = $this->findOrCreateRoute($path, $persistedLocale);
        $routeDocument->setTargetDocument($document);
        $routeDocument->setLocale($locale->getLocalization());
        $routeDocument->setHistory(false);

        $this->documentManager->persist(
            $routeDocument,
            $persistedLocale,
            [
                'path' => $path,
                'auto_create' => true,
            ]
        );

        return $routeDocument;
    }

    /**
     * @param $path
     * @param $locale
     *
     * @return RouteDocument
     */
    private function findOrCreateRoute($path, $locale)
    {
        try {
            return $this->documentManager->find($path, $locale);
        } catch (DocumentNotFoundException $ex) {
            return $this->documentManager->create('route');
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
        $document->setRoutes($this->findReferrer($document, $webspaceKey));
    }

    public function findReferrer($document, $webspaceKey)
    {
        $routes = [];
        $referrers = $this->inspector->getReferrers($document);
        foreach ($referrers as $routeDocument) {
            if ($routeDocument instanceof RouteDocument) {
                $path = PathHelper::relativizePath(
                    $routeDocument->getPath(),
                    $this->getRoutesPath($webspaceKey)
                );

                $routes[$path] = $routeDocument;
                $routes = array_merge($routes, $this->findReferrer($routeDocument, $webspaceKey));
            }
        }

        return $routes;
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

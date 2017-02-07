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
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
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
        return [
            Events::PERSIST => 'handlePersist',
            Events::HYDRATE => 'handleHydrate',
            Events::REMOVE => ['handleRemove', 550],
        ];
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
        $route = $this->createRoute(
            $domain,
            $document,
            $locale,
            $event->getLocale(),
            $this->getRoutesPath($webspaceKey)
        );

        if (!array_key_exists($domain, $oldRoutes)) {
            $document->addRoute($domain, $route);
        }

        foreach ($oldRoutes as $oldRoute) {
            if ($oldRoute->getPath() === $route->getPath()) {
                continue;
            }

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
            $this->documentManager->publish($oldRoute, $locale);
        }
    }

    /**
     * Create route-document for given domain.
     *
     * @param string $domain
     * @param CustomUrlBehavior $document
     * @param Localization $locale
     * @param string $persistedLocale
     * @param string $routesPath
     *
     * @return RouteDocument
     *
     * @throws ResourceLocatorAlreadyExistsException
     */
    protected function createRoute(
        $domain,
        CustomUrlBehavior $document,
        Localization $locale,
        $persistedLocale,
        $routesPath
    ) {
        $path = sprintf('%s/%s', $routesPath, $domain);
        $routeDocument = $this->findOrCreateRoute($path, $persistedLocale, $document, $domain);
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
        $this->documentManager->publish($routeDocument, $persistedLocale);

        return $routeDocument;
    }

    /**
     * Find or create route-document for given path.
     *
     * @param string $path
     * @param string $locale
     * @param CustomUrlBehavior $document
     * @param string $route
     *
     * @return RouteDocument
     *
     * @throws ResourceLocatorAlreadyExistsException
     */
    protected function findOrCreateRoute($path, $locale, CustomUrlBehavior $document, $route)
    {
        try {
            /** @var RouteDocument $routeDocument */
            $routeDocument = $this->documentManager->find($path, $locale);
        } catch (DocumentNotFoundException $ex) {
            return $this->documentManager->create('custom_url_route');
        }

        if (!$routeDocument instanceof RouteDocument
            || $routeDocument->getTargetDocument()->getUuid() !== $document->getUuid()
        ) {
            throw new ResourceLocatorAlreadyExistsException($route, $document->getTitle());
        }

        return $routeDocument;
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

    /**
     * Removes the routes for the given document.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();
        if (!($document instanceof CustomUrlBehavior)) {
            return;
        }

        foreach ($this->inspector->getReferrers($document) as $referrer) {
            if ($referrer instanceof RouteBehavior) {
                $this->documentManager->remove($referrer);
            }
        }
    }

    /**
     * Returns all route-document which referees given document.
     *
     * @param $document
     * @param $webspaceKey
     *
     * @return array
     */
    protected function findReferrer($document, $webspaceKey)
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
                $tmp = $this->findReferrer($routeDocument, $webspaceKey);
                $routes = array_merge($routes, $tmp);
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
    protected function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom_urls%', '%custom_urls_routes%']);
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use PHPCR\Util\PathHelper;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlCreatedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlModifiedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRemovedEvent;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRouteRemovedEvent;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\Exception\NodeNameAlreadyExistsException;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Webspace\CustomUrl;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Manages custom-url documents and their routes.
 */
class CustomUrlManager implements CustomUrlManagerInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @var CustomUrlRepository
     */
    private $customUrlRepository;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var DocumentDomainEventCollectorInterface
     */
    private $documentDomainEventCollector;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        CustomUrlRepository $customUrlRepository,
        MetadataFactoryInterface $metadataFactory,
        PathBuilder $pathBuilder,
        WebspaceManagerInterface $webspaceManager,
        $environment,
        DocumentDomainEventCollectorInterface $documentDomainEventCollector
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->customUrlRepository = $customUrlRepository;
        $this->metadataFactory = $metadataFactory;
        $this->pathBuilder = $pathBuilder;
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
        $this->documentDomainEventCollector = $documentDomainEventCollector;
    }

    public function create($webspaceKey, array $data)
    {
        /** @var CustomUrlDocument $document */
        $document = $this->documentManager->create('custom_url');
        $this->bind($document, $data);

        try {
            $this->documentManager->persist(
                $document,
                CustomUrlDocument::DOCUMENT_LOCALE,
                [
                    'parent_path' => $this->getItemsPath($webspaceKey),
                    'load_ghost_content' => true,
                    'auto_rename' => false,
                ]
            );
            $this->documentManager->publish($document, CustomUrlDocument::DOCUMENT_LOCALE);
            $this->documentDomainEventCollector->collect(new CustomUrlCreatedEvent($document, $webspaceKey, $data));
        } catch (NodeNameAlreadyExistsException $ex) {
            throw new TitleAlreadyExistsException($document->getTitle(), $ex);
        }

        return $document;
    }

    public function findList($webspaceKey)
    {
        // TODO pagination

        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $customUrls = $webspace->getPortals()[0]->getEnvironment($this->environment)->getCustomUrls();

        $baseDomains = \array_map(
            function(CustomUrl $customUrl) {
                return $customUrl->getUrl();
            },
            $customUrls
        );

        return $this->customUrlRepository->findList($this->getItemsPath($webspaceKey), $baseDomains);
    }

    public function findUrls($webspaceKey)
    {
        return $this->customUrlRepository->findUrls($this->getItemsPath($webspaceKey));
    }

    public function findHistoryRoutesById(string $id, string $webspaceKey)
    {
        $customUrlDocument = $this->find($id);

        $routeDocuments = $this->findReferrer($customUrlDocument, $webspaceKey);

        return \array_filter($routeDocuments, function($routeDocument) {
            return $routeDocument->isHistory();
        });
    }

    private function findReferrer($document, string $webspaceKey)
    {
        $routes = [];
        $referrers = $this->documentInspector->getReferrers($document);
        foreach ($referrers as $routeDocument) {
            if ($routeDocument instanceof RouteDocument) {
                $path = PathHelper::relativizePath(
                    $routeDocument->getPath(),
                    $this->getRoutesPath($webspaceKey)
                );

                $routes[$path] = $routeDocument;
                $tmp = $this->findReferrer($routeDocument, $webspaceKey);
                $routes = \array_merge($routes, $tmp);
            }
        }

        return $routes;
    }

    public function find($uuid)
    {
        return $this->documentManager->find($uuid, CustomUrlDocument::DOCUMENT_LOCALE, ['load_ghost_content' => true]);
    }

    public function findByUrl($url, $webspaceKey, $locale = null)
    {
        $routeDocument = $this->findRouteByUrl($url, $webspaceKey, $locale);

        if (null === $routeDocument) {
            return;
        }

        return $routeDocument->getTargetDocument();
    }

    public function findByPage(UuidBehavior $page)
    {
        $webspaceKeys = \array_map(function(Webspace $webspace) {
            return $webspace->getKey();
        }, $this->webspaceManager->getWebspaceCollection()->getWebspaces());
        if ($page instanceof WebspaceBehavior) {
            $webspaceKeys = [$page->getWebspaceName()];
        }

        $descendentPaths = [];
        foreach ($webspaceKeys as $webspaceKey) {
            $descendentPaths[] = \sprintf('ISDESCENDANTNODE(a, "/cmf/%s/custom-urls")', $webspaceKey);
        }

        $query = $this->documentManager->createQuery(
            \sprintf(
                'SELECT * FROM [nt:unstructured] AS a WHERE (%s) AND a.[jcr:mixinTypes] = "sulu:custom_url" AND a.[sulu:target] = "%s"',
                \implode(' OR ', $descendentPaths),
                $page->getUuid()
            )
        );

        return $query->execute();
    }

    public function findRouteByUrl($url, $webspaceKey, $locale = null)
    {
        try {
            /** @var RouteDocument $routeDocument */
            $routeDocument = $this->documentManager->find(
                \sprintf('%s/%s', $this->getRoutesPath($webspaceKey), $url),
                $locale,
                ['load_ghost_content' => true]
            );
        } catch (DocumentNotFoundException $ex) {
            return;
        }

        if (!$routeDocument instanceof RouteDocument) {
            return;
        }

        return $routeDocument;
    }

    /**
     * Read route of custom-url identified by uuid.
     *
     * @param string $uuid
     *
     * @return RouteDocument
     */
    protected function findRoute($uuid)
    {
        $document = $this->documentManager->find($uuid);

        if (!$document instanceof RouteDocument) {
            return;
        }

        return $document;
    }

    public function save($uuid, array $data)
    {
        $document = $this->find($uuid);
        $this->bind($document, $data);

        try {
            $this->documentManager->persist(
                $document,
                CustomUrlDocument::DOCUMENT_LOCALE,
                [
                    'parent_path' => PathHelper::getParentPath($document->getPath()),
                    'load_ghost_content' => true,
                    'auto_rename' => false,
                    'auto_name_locale' => CustomUrlDocument::DOCUMENT_LOCALE,
                ]
            );
            $this->documentManager->publish($document, CustomUrlDocument::DOCUMENT_LOCALE);
            $this->documentDomainEventCollector->collect(
                new CustomUrlModifiedEvent($document, $this->documentInspector->getWebspace($document), $data)
            );
        } catch (NodeNameAlreadyExistsException $ex) {
            throw new TitleAlreadyExistsException($document->getTitle(), $ex);
        }

        return $document;
    }

    public function delete($uuid)
    {
        $document = $this->find($uuid);
        $webspaceKey = $this->documentInspector->getWebspace($document);
        $this->documentManager->remove($document);

        $this->documentDomainEventCollector->collect(new CustomUrlRemovedEvent(
            $document->getUuid(),
            $document->getTitle(),
            $webspaceKey
        ));

        return $document;
    }

    public function deleteRoute($webspaceKey, $uuid)
    {
        $routeDocument = $this->findRoute($uuid);

        // target of route is a another route for history routes, therefore loop until we find the custom url
        $customUrlDocument = $routeDocument->getTargetDocument();
        while (!$customUrlDocument instanceof CustomUrlDocument) {
            $customUrlDocument = $customUrlDocument->getTargetDocument();
        }

        if (!$routeDocument->isHistory()) {
            $route = PathHelper::relativizePath($routeDocument->getPath(), $this->getRoutesPath($webspaceKey));

            throw new RouteNotRemovableException($route, $routeDocument, $customUrlDocument);
        }

        $this->documentManager->remove($routeDocument);

        $this->documentDomainEventCollector->collect(new CustomUrlRouteRemovedEvent(
            $customUrlDocument,
            $webspaceKey,
            $routeDocument->getUuid()
        ));

        return $routeDocument;
    }

    public function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom_urls%', '%custom_urls_routes%']);
    }

    /**
     * Bind data array to given document.
     *
     * TODO this logic have to be extracted in a proper way.
     *
     * @param array $data
     */
    private function bind(CustomUrlDocument $document, $data)
    {
        $document->setTitle($data['title']);
        unset($data['title']);

        $metadata = $this->metadataFactory->getMetadataForAlias('custom_url');

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($metadata->getFieldMappings() as $fieldName => $mapping) {
            if (!\array_key_exists($fieldName, $data)) {
                continue;
            }

            $value = $data[$fieldName];
            if (\array_key_exists('type', $mapping) && 'reference' === $mapping['type']) {
                $value = $this->documentManager->find($value, CustomUrlDocument::DOCUMENT_LOCALE, ['load_ghost_content' => true]);
            }

            $accessor->setValue($document, $fieldName, $value);
        }
    }

    /**
     * Returns path to custom-url documents.
     *
     * @return string
     */
    private function getItemsPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom_urls%', '%custom_urls_items%']);
    }
}

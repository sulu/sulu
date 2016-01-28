<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Ferrandini\Urlizer;
use PHPCR\ItemExistsException;
use PHPCR\Util\PathHelper;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\PathBuilder;
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

    public function __construct(
        DocumentManagerInterface $documentManager,
        CustomUrlRepository $customUrlRepository,
        MetadataFactoryInterface $metadataFactory,
        PathBuilder $pathBuilder
    ) {
        $this->documentManager = $documentManager;
        $this->customUrlRepository = $customUrlRepository;
        $this->metadataFactory = $metadataFactory;
        $this->pathBuilder = $pathBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function create($webspaceKey, array $data, $locale = null)
    {
        $document = $this->documentManager->create('custom_urls');
        $this->bind($document, $data, $locale);

        try {
            $this->documentManager->persist(
                $document,
                $locale,
                [
                    'parent_path' => $this->getItemsPath($webspaceKey),
                    'node_name' => Urlizer::urlize($document->getTitle()),
                    'load_ghost_content' => true,
                ]

            );
        } catch (ItemExistsException $ex) {
            throw new TitleExistsException($document->getTitle());
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function readList($webspaceKey, $locale)
    {
        // TODO pagination

        return $this->customUrlRepository->findList($this->getItemsPath($webspaceKey), $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function read($uuid, $locale = null)
    {
        return $this->documentManager->find($uuid, $locale, ['load_ghost_content' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function readByUrl($url, $webspaceKey, $locale = null)
    {
        $routeDocument = $this->readRouteByUrl($url, $webspaceKey, $locale);

        if ($routeDocument === null) {
            return;
        }

        return $routeDocument->getTargetDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function readRouteByUrl($url, $webspaceKey, $locale = null)
    {
        try {
            /** @var RouteDocument $routeDocument */
            $routeDocument = $this->documentManager->find(
                sprintf('%s/%s', $this->getRoutesPath($webspaceKey), $url),
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
    protected function readRoute($uuid)
    {
        $document = $this->documentManager->find($uuid);

        if (!$document instanceof RouteDocument) {
            return;
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function update($uuid, array $data, $locale = null)
    {
        $document = $this->read($uuid, $locale);
        $this->bind($document, $data, $locale);

        try {
            $this->documentManager->persist(
                $document,
                $locale,
                [
                    'parent_path' => PathHelper::getParentPath($document->getPath()),
                    'node_name' => Urlizer::urlize($document->getTitle()),
                    'load_ghost_content' => true,
                ]
            );
        } catch (ItemExistsException $ex) {
            throw new TitleExistsException($document->getTitle());
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($uuid)
    {
        $this->documentManager->remove($this->read($uuid));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRoute($webspaceKey, $uuid)
    {
        $routeDocument = $this->readRoute($uuid);

        if (!$routeDocument->isHistory()) {
            $route = PathHelper::relativizePath($routeDocument->getPath(), $this->getRoutesPath($webspaceKey));

            throw new CannotDeleteCurrentRouteException($route, $routeDocument, $routeDocument->getTargetDocument());
        }

        $this->documentManager->remove($routeDocument);
    }

    /**
     * Bind data array to given document.
     *
     * TODO this logic have to be extracted in a proper way.
     *
     * @param CustomUrlDocument $document
     * @param array $data
     * @param string $locale
     */
    private function bind(CustomUrlDocument $document, $data, $locale)
    {
        $metadata = $this->metadataFactory->getMetadataForAlias('custom_urls');

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($metadata->getFieldMappings() as $fieldName => $mapping) {
            if (!array_key_exists($fieldName, $data) || empty($data[$fieldName])) {
                continue;
            }

            $value = $data[$fieldName];
            if (array_key_exists('type', $mapping) && $mapping['type'] === 'reference') {
                $value = $this->documentManager->find($value['uuid'], $locale, ['load_ghost_content' => true]);
            }

            $accessor->setValue($document, $fieldName, $value);
        }

        $document->setLocale($locale);
    }

    /**
     * Returns path to custom-url documents.
     *
     * @return string
     */
    private function getItemsPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom-urls%', '%custom-urls-items%']);
    }

    /**
     * Returns base path to custom-url routes.
     *
     * @return string
     */
    private function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom-urls%', '%custom-urls-routes%']);
    }
}

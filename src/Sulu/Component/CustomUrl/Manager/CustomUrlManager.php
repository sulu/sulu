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
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
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
     * @var PathBuilder
     */
    private $pathBuilder;

    public function __construct(
        DocumentManagerInterface $documentManager,
        CustomUrlRepository $customUrlRepository,
        PathBuilder $pathBuilder
    ) {
        $this->documentManager = $documentManager;
        $this->customUrlRepository = $customUrlRepository;
        $this->pathBuilder = $pathBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function create($webspaceKey, array $data)
    {
        $document = $this->documentManager->create('custom_urls');
        $this->bind($document, $data);

        $this->documentManager->persist(
            $document,
            null,
            [
                'parent_path' => $this->getItemsPath($webspaceKey),
                'node_name' => Urlizer::urlize($document->getTitle()),
            ]
        );

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function readList($webspaceKey)
    {
        // TODO pagination

        return $this->customUrlRepository->findList($this->getItemsPath($webspaceKey));
    }

    /**
     * {@inheritdoc}
     */
    public function read($uuid)
    {
        return $this->documentManager->find($uuid);
    }

    /**
     * {@inheritdoc}
     */
    public function update($uuid, array $data)
    {
        $document = $this->read($uuid);
        $this->bind($document, $data);

        $this->documentManager->persist($document);

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
    public function getFields()
    {
        return [
            'title' => ['property' => 'title'],
            'published' => ['property' => 'published'],
            'baseDomain' => ['property' => 'baseDomain'],
            'domainParts' => ['property' => 'domainParts', 'type' => 'json_array'],
            'multilingual' => ['property' => 'multilingual'],
            'canonical' => ['property' => 'canonical'],
            'redirect' => ['property' => 'redirect'],
        ];
    }

    /**
     * Bind data array to given document.
     *
     * TODO find document for target (type reference) and set it to custom-url.
     *
     * @param CustomUrlDocument $document
     * @param array $data
     */
    private function bind(CustomUrlDocument $document, $data)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->getFields() as $fieldName => $mapping) {
            $accessor->setValue($document, $fieldName, $data[$fieldName]);
        }
    }

    /**
     * {@inheritdoc}
     */
    private function getItemsPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom-urls%', '%custom-urls-items%']);
    }
}

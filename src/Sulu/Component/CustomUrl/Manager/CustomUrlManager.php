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
    public function create($webspaceKey, array $data, $locale = null)
    {
        $document = $this->documentManager->create('custom_urls');
        $this->bind($document, $data, $locale);

        $this->documentManager->persist(
            $document,
            $locale,
            [
                'parent_path' => $this->getItemsPath($webspaceKey),
                'node_name' => Urlizer::urlize($document->getTitle()),
                'load_ghost_content' => true,
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
    public function read($uuid, $locale = null)
    {
        return $this->documentManager->find($uuid, $locale, ['load_ghost_content' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function update($uuid, array $data, $locale = null)
    {
        $document = $this->read($uuid, $locale);
        $this->bind($document, $data, $locale);

        $this->documentManager->persist($document, $locale);

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
            'target' => ['property' => 'target', 'type' => 'reference'],
            'multilingual' => ['property' => 'multilingual'],
            'canonical' => ['property' => 'canonical'],
            'redirect' => ['property' => 'redirect'],
            'targetLocale' => ['property' => 'targetLocale'],
        ];
    }

    /**
     * Bind data array to given document.
     *
     * @param CustomUrlDocument $document
     * @param array $data
     * @param string $locale
     */
    private function bind(CustomUrlDocument $document, $data, $locale)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->getFields() as $fieldName => $mapping) {
            $value = $data[$fieldName];
            if (array_key_exists('type', $mapping) && $mapping['type'] === 'reference') {
                $value = $this->documentManager->find($value['uuid'], $locale, ['load_ghost_content' => true]);
            }

            $accessor->setValue($document, $fieldName, $value);
        }

        $document->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    private function getItemsPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom-urls%', '%custom-urls-items%']);
    }
}

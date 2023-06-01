<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Converter;

use Massive\Bundle\SearchBundle\Search\Converter\ConverterInterface;
use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\ObjectToDocumentConverter;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StructureConverter implements ConverterInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var ObjectToDocumentConverter
     */
    private $objectToDocumentConverter;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        DocumentManagerInterface $documentManager,
        SearchManagerInterface $searchManager,
        ObjectToDocumentConverter $objectToDocumentConverter,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->documentManager = $documentManager;
        $this->searchManager = $searchManager;
        $this->objectToDocumentConverter = $objectToDocumentConverter;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function convert($value, ?Document $document = null)
    {
        if (null === $value) {
            return null;
        }

        if (null === $document) {
            return $value;
        }

        $locale = $document->getLocale();
        $fields = [];

        if (\is_string($value)) {
            $fields = $this->getFieldsById($value, $locale);
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                if (null === $item || !\is_string($item)) {
                    continue;
                }

                foreach ($this->getFieldsById($item, $locale) as $field) {
                    if (null === $field) {
                        continue;
                    }

                    $field = clone $field;
                    $field->setName($key . '#' . $field->getName());

                    $fields[] = $field;
                }
            }
        }

        return [
            'value' => $value,
            'fields' => $fields,
        ];
    }

    /**
     * @return Field[]
     */
    private function getFieldsById(string $id, string $locale): array
    {
        try {
            $object = $this->documentManager->find($id, $locale);
            $document = $this->convertObjectToDocument($object);

            return $document->getFields();
        } catch (DocumentManagerException $e) {
            return [];
        }
    }

    private function convertObjectToDocument(object $object): Document
    {
        $indexMetadata = $this->searchManager->getMetadata($object);
        $defaultIndexMetadata = $indexMetadata->getIndexMetadata('_default');
        $document = $this->objectToDocumentConverter->objectToDocument($defaultIndexMetadata, $object);
        $evaluator = $this->objectToDocumentConverter->getFieldEvaluator();

        $this->eventDispatcher->dispatch(
            new PreIndexEvent($object, $document, $defaultIndexMetadata, $evaluator),
            SearchEvents::PRE_INDEX
        );

        return $document;
    }
}

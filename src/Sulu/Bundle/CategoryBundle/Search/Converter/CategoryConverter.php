<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Search\Converter;

use Massive\Bundle\SearchBundle\Search\Converter\ConverterInterface;
use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\ObjectToDocumentConverter;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CategoryConverter implements ConverterInterface
{
    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

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
        CategoryManagerInterface $categoryManager,
        SearchManagerInterface $searchManager,
        ObjectToDocumentConverter $objectToDocumentConverter,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->categoryManager = $categoryManager;
        $this->searchManager = $searchManager;
        $this->objectToDocumentConverter = $objectToDocumentConverter;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function convert($value, Document $document = null)
    {
        if (null === $value) {
            return null;
        }

        if (null === $document) {
            return $value;
        }

        $locale = $document->getLocale();

        $fields = [];

        if (\is_integer($value)) {
            $fields = $this->getFieldsById($value, $locale);
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                if (null === $item || !\is_integer($item)) {
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
    private function getFieldsById(int $id, ?string $locale): array
    {
        try {
            $category = $this->categoryManager->findById($id);
            $categoryTranslation = $category->findTranslationByLocale($locale);

            if (false === $categoryTranslation) {
                $categoryTranslation = $category->findTranslationByLocale($category->getDefaultLocale());
            }

            if (false === $categoryTranslation) {
                return [];
            }

            $document = $this->convertObjectToDocument($categoryTranslation);

            return $document->getFields();
        } catch (CategoryIdNotFoundException $e) {
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

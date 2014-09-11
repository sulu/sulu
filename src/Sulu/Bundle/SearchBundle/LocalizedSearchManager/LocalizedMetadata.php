<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\LocalizedSearchManager;

use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;

/**
 * Add localization to metadata
 * @package Sulu\Bundle\SearchBundle\Metadata
 */
class LocalizedMetadata implements IndexMetadataInterface
{
    /**
     * @var IndexMetadataInterface
     */
    private $metadata;

    /**
     * @var string
     */
    private $locale;

    function __construct(IndexMetadataInterface $metadata, $locale)
    {
        $this->locale = $locale;
        $this->metadata = $metadata;
    }

    public function setUrlField($urlField)
    {
        $this->metadata->setUrlField($urlField);
    }

    public function setDescriptionField($descriptionField)
    {
        $this->metadata->setDescriptionField($descriptionField);
    }

    public function getFieldMapping()
    {
        return $this->metadata->getFieldMapping();
    }

    public function setTitleField($titleField)
    {
        $this->metadata->setTitleField($titleField);
    }

    public function getUrlField()
    {
        return $this->metadata->getUrlField();
    }

    public function getIdField()
    {
        return $this->metadata->getIdField();
    }

    public function setIndexName($indexName)
    {
        $this->metadata->setIndexName($indexName);
    }

    public function addFieldMapping($name, $mapping)
    {
        $this->metadata->addFieldMapping($name, $mapping);
    }

    public function setFieldMapping($fieldMapping)
    {
        $this->metadata->setFieldMapping($fieldMapping);
    }

    public function getTitleField()
    {
        return $this->metadata->getTitleField();
    }

    public function getIndexName()
    {
        return $this->metadata->getIndexName() . '_' . $this->locale;
    }

    public function setIdField($idField)
    {
        $this->metadata->setIdField($idField);
    }

    public function getDescriptionField()
    {
        return $this->metadata->getDescriptionField();
    }

    public function getName()
    {
        return $this->metadata->getName();
    }
}

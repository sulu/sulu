<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content;

use JMS\Serializer\Serializer;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use JMS\Serializer\Annotation\Exclude;

/**
 * Container for InternalLinks, holds the config for a internal links, and lazy loads the structures
 * @package Sulu\Bundle\ContentBundle\Content
 */
class InternalLinksContainer implements \Serializable
{
    /**
     * The node repository, which is needed for lazy loading
     * @Exclude
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * The key of the webspace
     * @Exclude
     * @var string
     */
    private $webspaceKey;

    /**
     * The code of the language
     * @Exclude
     * @var string
     */
    private $languageCode;

    /**
     * @var string[]
     */
    private $ids;

    /**
     * @Exclude
     * @var StructureInterface[]
     */
    private $data;

    public function __construct(
        $ids,
        ContentMapperInterface $contentMapper,
        $webspaceKey,
        $languageCode
    ) {
        $this->ids = $ids;
        $this->contentMapper = $contentMapper;
        $this->webspaceKey = $webspaceKey;
        $this->languageCode = $languageCode;
    }

    /**
     * Lazy loads the data based on the filter criteria from the config
     * @return StructureInterface[]
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    /**
     * lazy load data
     */
    private function loadData()
    {
        $result = array();

        if ($this->ids !== null) {
            foreach ($this->ids as $id) {
                $result[] = $this->contentMapper->load($id, $this->webspaceKey, $this->languageCode);
            }
        }

        return $result;
    }

    /**
     * magic getter
     */
    public function __get($name)
    {
        switch ($name) {
            case 'data':
                return $this->getData();
        }
        return null;
    }

    /**
     * magic isset
     */
    public function __isset($name)
    {
        return ($name == 'data');
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        $result = array();
        foreach ($this->getData() as $item) {
            if ($item instanceof StructureInterface) {
                $result[] = $item->toArray();
            } else {
                $result[] = $item;
            }
        }

        return serialize(
            array(
                'data' => $result
            )
        );
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $values = unserialize($serialized);
        $this->data = $values['data'];
    }
}

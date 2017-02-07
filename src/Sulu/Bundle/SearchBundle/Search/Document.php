<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search;

use Massive\Bundle\SearchBundle\Search\Document as BaseDocument;

/**
 * Custom search document class for Sulu which includes blame
 * and timestamp fields.
 */
class Document extends BaseDocument
{
    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var string
     */
    protected $creatorName;

    /**
     * @var int
     */
    protected $creatorId;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var string
     */
    protected $changerName;

    /**
     * @var int
     */
    protected $changerId;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param string $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param string $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * @return string
     */
    public function getChangerName()
    {
        return $this->changerName;
    }

    /**
     * @param string $changerName
     */
    public function setChangerName($changerName)
    {
        $this->changerName = $changerName;
    }

    /**
     * @return string
     */
    public function getCreatorName()
    {
        return $this->creatorName;
    }

    /**
     * @param string $creatorName
     */
    public function setCreatorName($creatorName)
    {
        $this->creatorName = $creatorName;
    }

    /**
     * @return int
     */
    public function getChangerId()
    {
        return $this->changerId;
    }

    /**
     * @param int $changerId
     */
    public function setChangerId($changerId)
    {
        $this->changerId = $changerId;
    }

    /**
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * @param int $creatorId
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Called by the JMS Serializer before the document is serialized for the
     * web API.
     *
     * Here we remove all system fields (which are available in the body
     * of the document anyway).
     */
    public function removeSystemFields()
    {
        $properties = $this->fields;
        unset(
            $properties['created'],
            $properties['changed'],
            $properties['changer'],
            $properties['changer_id'],
            $properties['creator'],
            $properties['creator_id']
        );

        foreach ($properties as $key => $field) {
            // remove system fields
            if (substr($key, 0, 2) == '__') {
                continue;
            }

            $this->properties[$key] = $field->getValue();
        }
    }
}

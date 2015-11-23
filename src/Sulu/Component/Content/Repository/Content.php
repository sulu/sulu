<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository;

use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Exception\FeatureNotImplementedException;

/**
 * Container class for content data.
 *
 * @ExclusionPolicy("all")
 */
class Content implements \ArrayAccess, \JsonSerializable
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $workflowStage;

    /**
     * @var int
     */
    private $nodeType;

    /**
     * @var bool
     */
    private $hasChildren;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var StructureType
     */
    private $localizationType;

    public function __construct(
        $locale,
        $webspaceKey,
        $uuid,
        $path,
        $workflowStage,
        $nodeType,
        $hasChildren,
        array $data,
        array $permissions,
        StructureType $localizationType = null
    ) {
        $this->uuid = $uuid;
        $this->path = $path;
        $this->workflowStage = $workflowStage;
        $this->nodeType = $nodeType;
        $this->hasChildren = $hasChildren;
        $this->data = $data;
        $this->permissions = $permissions;
        $this->localizationType = $localizationType;
        $this->locale = $locale;
        $this->webspaceKey = $webspaceKey;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getWorkflowStage()
    {
        return $this->workflowStage;
    }

    /**
     * @return int
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->hasChildren;
    }

    /**
     * @return StructureType
     */
    public function getLocalizationType()
    {
        return $this->localizationType;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return array_merge(
            ['uuid' => $this->getUuid(), 'path' => $this->getPath(), 'hasChildren' => $this->hasChildren()],
            $this->getData()
        );
    }
}

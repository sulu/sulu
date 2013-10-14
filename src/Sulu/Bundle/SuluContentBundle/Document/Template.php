<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations\Document;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Id;
use Doctrine\ODM\PHPCR\Mapping\Annotations\ParentDocument;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Nodename;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Children;
use Doctrine\ODM\PHPCR\Mapping\Annotations\String;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Long;
use Doctrine\ODM\PHPCR\Mapping\Annotations\MappedSuperclass;

/**
 * Class Template
 * @package Sulu\Bundle\ContentBundle\Document
 *
 * @MappedSuperClass(
 *   versionable=true,
 *   referenceable=true,
 *   translator="child"
 * )
 */
abstract class Template
{
    /**
     * @PHPCR\Id()
     */
    protected $id;

    /**
     * @PHPCR\ParentDocument()
     */
    protected $parent;

    /**
     * @PHPCR\NodeName()
     */
    protected $name;

    /**
     * @String()
     */
    protected $key;

    /**
     * @Long()
     */
    protected $cacheLifetime;

    function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $cacheLifetime
     */
    public function setCacheLifetime($cacheLifetime)
    {
        $this->cacheLifetime = $cacheLifetime;
    }

    /**
     * @return mixed
     */
    public function getCacheLifetime()
    {
        return $this->cacheLifetime;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }


}

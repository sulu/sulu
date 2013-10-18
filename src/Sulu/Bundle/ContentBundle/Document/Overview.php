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
 * Class Overview
 * @package Sulu\Bundle\ContentBundle\Document
 *
 * @Document()
 */
class Overview extends Template
{
    /**
     * @String(translated=true)
     */
    protected $title;

    /**
     * @String(translated=true)
     */
    protected $article;

    function __construct()
    {
    }

    /**
     * @param mixed $article
     */
    public function setArticle($article)
    {
        $this->article = $article;
    }

    /**
     * @return mixed
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }
}

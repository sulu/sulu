<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Event;

use Sulu\Bundle\TagBundle\Entity\Tag;
use Symfony\Component\EventDispatcher\Event;

/**
 * An object of this class is thrown along with the tag.merge event
 * @package Sulu\Bundle\TagBundle\Event
 */
class TagMergeEvent extends Event
{
    /**
     * The deleted Tag
     * @var Tag
     */
    protected $srcTag;

    /**
     * The Tag the deleted Tag got merged into
     * @var Tag
     */
    protected $destTag;

    /**
     * @param Tag $srcTag The deleted Tag
     * @param Tag $destTag The Tag the deleted Tag got merged into
     */
    public function __construct(Tag $srcTag, Tag $destTag)
    {
        $this->srcTag = $srcTag;
        $this->destTag = $destTag;
    }

    /**
     * Returns the Tag which got deleted
     * @return Tag
     */
    public function getSrcTag()
    {
        return $this->srcTag;
    }

    /**
     * Returns the Tag in which the deleted Tag got merged
     * @return Tag
     */
    public function getDestTag()
    {
        return $this->destTag;
    }
} 

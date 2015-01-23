<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * Page Structure class
 *
 * @PHPCR\Document(
 *     nodeType="sulu:page"
 * )
 */
class PageDocument extends StructureDocument
{
    /**
     * Published state
     *
     * @PHPCR\String()
     */
    protected $state = 'unpublished';

    public function getState() 
    {
        return $this->state;
    }
    
    public function setState($state)
    {
        $this->state = $state;
    }
}

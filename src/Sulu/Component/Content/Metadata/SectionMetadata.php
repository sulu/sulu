<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

/**
 * Metadata for a section. A section is a UI component which
 * groups a bunch of properties.
 */
class SectionMetadata extends ItemMetadata
{
    /**
     * The number of grid columns the property should use in the admin interface
     *
     * @var integer
     */
    public $colSpan = null;

    /**
     * Return the colspan.
     *
     * @return integer
     */
    public function getColSpan() 
    {
        return $this->colSpan;
    }
    
}

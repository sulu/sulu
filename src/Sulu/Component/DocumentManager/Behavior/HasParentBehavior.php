<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior;

/**
 * The document has a parent document
 */
interface HasParentBehavior
{
    /**
     * Return the parent document for this document
     *
     * @return object
     */
    public function getParent();

    /**
     * Set the parent document for this document
     */
    public function setParent($document);
}


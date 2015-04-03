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
 * The PHPCR nodes of objects implementing this behavior will have
 * names automatically assigned based on their title.
 */
interface AutoNameBehavior extends HasParentBehavior
{
    /**
     * Return a title to be used as the seed for the node name
     *
     * @return string
     */
    public function getTitle();
}

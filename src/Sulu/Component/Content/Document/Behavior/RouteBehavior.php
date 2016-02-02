<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * Routes are documents which represent a URL segment and provide
 * a reference to the document which should form the basis of the
 * page rendered when the URL is accessed.
 */
interface RouteBehavior
{
    /**
     * Get the route target document.
     *
     * @return object
     */
    public function getTargetDocument();

    /**
     * Set the target document.
     *
     * @param object $targetDocument
     */
    public function setTargetDocument($targetDocument);
}

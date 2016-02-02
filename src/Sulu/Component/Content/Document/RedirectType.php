<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document;

/**
 * Constants class for redirect types.
 */
final class RedirectType
{
    /**
     * indicates that the node is a content node.
     */
    const NONE = 1;

    /**
     * indicates that the node links to an internal resource.
     */
    const INTERNAL = 2;

    /**
     * indicates that the node links to an external resource.
     */
    const EXTERNAL = 4;
}

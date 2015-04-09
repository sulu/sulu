<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Stucture;

class LegacyStructureConstants
{
    /**
     * indicates that the node is a content node
     */
    const NODE_TYPE_CONTENT = 1;

    /**
     * indicates that the node links to an internal resource
     */
    const NODE_TYPE_INTERNAL_LINK = 2;

    /**
     * indicates that the node links to an external resource
     */
    const NODE_TYPE_EXTERNAL_LINK = 4;

    /**
     * Structure type page
     */
    const TYPE_PAGE = 'page';

    /**
     * Structure type page
     */
    const TYPE_SNIPPET = 'snippet';
}

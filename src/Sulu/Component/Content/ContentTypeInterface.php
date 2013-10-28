<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use PHPCR\NodeInterface;

/**
 * ContentType
 */
interface ContentTypeInterface
{
    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @return mixed
     */
    public function get(NodeInterface $node, PropertyInterface $property);

    /**
     * save the value from given property
     * @param NodeInterface $node
     * @param PropertyInterface $property
     */
    public function set(NodeInterface $node, PropertyInterface $property);
}

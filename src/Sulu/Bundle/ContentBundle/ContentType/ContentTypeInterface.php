<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\ContentType;

use PHPCR\NodeInterface;

/**
 * Defines an interface for the content types
 * @package Sulu\Bundle\ContentBundle\ContentType
 */
interface ContentTypeInterface
{
    public function save(NodeInterface $node, $data);
}

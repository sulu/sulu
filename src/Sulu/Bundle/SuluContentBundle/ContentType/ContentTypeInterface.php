<?php
/**
 * Created by IntelliJ IDEA.
 * User: danielrotter
 * Date: 14.10.13
 * Time: 22:45
 * To change this template use File | Settings | File Templates.
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

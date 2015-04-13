<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Structure\Structure;

/**
 * Container for content properties.
 */
interface PropertyContainerInterface extends \ArrayAccess
{
    /**
     * Return the named property
     *
     * @param string $name
     */
    public function getProperty($name);
}

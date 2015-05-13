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

use Sulu\Component\Content\Types\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Compat\Structure\Structure;

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

    /**
     * Return true if the container has the named property
     *
     * @param string $name
     */
    public function hasProperty($name);

    /**
     * Bind data to the container
     *
     * @param array $data
     * @param boolean $clearMissing
     */
    public function bind($data, $clearMissing);

    /**
     * Return an array representation of the containers property values
     *
     * @return array
     */
    public function toArray();
}

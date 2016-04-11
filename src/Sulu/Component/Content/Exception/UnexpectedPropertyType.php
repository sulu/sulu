<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ContentTypeInterface;

class UnexpectedPropertyType extends \Exception
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var ContentTypeInterface
     */
    private $contentType;

    public function __construct(PropertyInterface $property, ContentTypeInterface $contentType)
    {
        parent::__construct(sprintf('Property "%s" is unexcepted in content type "%s"', $property->getName(), get_class($contentType)));
        $this->property = $property;
        $this->contentType = $contentType;
    }

    /**
     * @return PropertyInterface
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return ContentTypeInterface
     */
    public function getContentType()
    {
        return $this->contentType;
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ContentTypeInterface;

class UnexpectedPropertyType extends \Exception
{
    public function __construct(private PropertyInterface $property, private ContentTypeInterface $contentType)
    {
        parent::__construct(
            \sprintf('Property "%s" is unexpected in content type "%s"', $property->getName(), \get_class($contentType))
        );
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

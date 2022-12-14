<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType;

use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Component\Content\Document\Structure\PropertyValue;

interface ReferenceContentTypeInterface
{
    public function getReferences(PropertyValue $property, ReferenceCollector $referenceCollector): void;
}

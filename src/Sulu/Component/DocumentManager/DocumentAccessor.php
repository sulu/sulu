<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class DocumentAccessor
{
    private $document;
    private $reflection;

    public function __construct($document)
    {
        $this->document = $document;
        $this->reflection = new \ReflectionClass($document);
    }

    public function set($field, $value)
    {
        if (!$this->has($field)) {
            throw new DocumentManagerException(sprintf(
                'Document "%s" must have property "%s" (it is probably required by a behavior)',
                get_class($this->document), $field
            ));
        }
        $property = $this->reflection->getProperty($field);
        $property->setAccessible(true);
        $property->setValue($this->document, $value);
    }

    public function has($field)
    {
        return $this->reflection->hasProperty($field);
    }
}

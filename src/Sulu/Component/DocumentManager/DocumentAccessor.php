<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class DocumentAccessor
{
    /**
     * @var object
     */
    private $document;

    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * @param $document
     */
    public function __construct($document)
    {
        $this->document = $document;
        $documentClass = get_class($document);

        if ($document instanceof LazyLoadingInterface) {
            $documentClass = ClassNameInflector::getUserClassName($documentClass);
        }

        $this->reflection = new \ReflectionClass($documentClass);
    }

    /**
     * @param string $field
     * @param mixed $value
     *
     * @throws DocumentManagerException
     */
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

    public function get($field)
    {
        $property = $this->reflection->getProperty($field);
        // TODO: Can be cached? Makes a performance diff?
        $property->setAccessible(true);

        return $property->getValue($this->document);
    }

    /**
     * @param $field
     *
     * @return bool
     */
    public function has($field)
    {
        return $this->reflection->hasProperty($field);
    }
}

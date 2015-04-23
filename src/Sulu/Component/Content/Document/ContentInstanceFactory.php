<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document;

use Sulu\Component\Content\Document\Property\PropertyContainer;
use Sulu\Component\Content\Document\Property\PropertyContainerWrapper;
use Sulu\Component\Content\Document\Property\PropertyContainerInterface;
use DTL\DecoratorGenerator\DecoratorFactory;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\ClassNameInflector;

/**
 * This class provides a way to decorate documents with classes which
 * encorporate the structure type in the name.
 *
 * This is necessary in order to be able to integrate content with systems
 * which use class based metadata systems.
 */
class ContentInstanceFactory
{
    const MARKER = '__DYNAMIC__';

    /**
     * @var DecoratorFactory
     */
    private $decoratorFactory;

    /**
     * @param DecoratorFactory $decoratorFactory
     */
    public function __construct(DecoratorFactory $decoratorFactory)
    {
        $this->decoratorFactory = $decoratorFactory;
    }

    /**
     * Wrap the given PropertyContainerInterface instance in a class
     * named after its structure type.
     *
     * The name encodes both the document alias and the structure name.
     *
     * @param string $documentAlias
     * @param string $structureType
     * @param PropertyContainerInterface $content
     *
     * @return PropertyContainerWrapper
     */
    public function getInstance(ContentBehavior $document)
    {
        return $this->decoratorFactory->decorate(
            $document, self::getTargetClassName(
                ClassNameInflector::getUserClassName(
                    get_class($document)
                ), 
                $document->getStructureType()
            )
        );
    }

    /**
     * Get the decorated class name
     *
     * @param string $className
     * @param string $structureType
     */
    public static function getTargetClassName($className, $structureType)
    {
        return sprintf('%s\\%s%s', $className, self::MARKER, $structureType);
    }

    /**
     * Return the structure type for the given wrapped property container
     *
     * @param PropertyContainerInterface $object
     *
     * @return string
     */
    public static function getStructureType($class)
    {
        self::assertWrapped($class);
        $structureType = substr(
            strstr($class, self::MARKER),
            strlen(self::MARKER)
        );

        return $structureType;
    }

    public static function getRealName($class)
    {
        self::assertWrapped($class);

        return substr(strstr($class, self::MARKER, true), 0, -1);
    }

    /**
     * Return true if the given class name contains the decorated marker
     *
     * @param string
     *
     * @return boolean
     */
    public static function isWrapped($class)
    {
        return false !== strpos($class, self::MARKER);
    }

    /**
     * Assert that the given class is decorated
     *
     * @param string $class
     *
     * @throws \RuntimeException If the class name is not decorated
     */
    private static function assertWrapped($class)
    {
        if (self::isWrapped($class)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'Cannot get structure type for non-wrapped propertyContainer class "%s"',
            $class
        ));
    }
}

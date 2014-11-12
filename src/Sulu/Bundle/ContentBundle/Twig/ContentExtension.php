<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Twig;

use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\PropertyInterface;

/**
 * Extension for content form generation
 * @package Sulu\Bundle\ContentBundle\Twig
 */
class ContentExtension extends \Twig_Extension
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    public function __construct($contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    /**
     * Returns an array of possible function in this extension
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('getType', array($this, 'getTypeFunction')),
            new \Twig_SimpleFunction('needsAddButton', array($this, 'needsAddButtonFunction')),
            new \Twig_SimpleFunction('getParams', array($this, 'getParamsFunction'))
        );
    }

    /**
     * @param PropertyInterface $property
     * @return array
     */
    public function getParamsFunction($property)
    {
        $typeParams = array();
        if ($this->contentTypeManager->has($property->getContentTypeName())) {
            $type = $this->getTypeFunction($property->getContentTypeName());
            $typeParams = $type->getDefaultParams();
        }

        return $this->mergeRecursive($typeParams, $property->getParams());
    }

    /**
     * Better array merge recursive function
     *  - does not combine to scalar values to a array
     * @see http://php.net/manual/de/function.array-merge-recursive.php#106985
     * @return array
     */
    private function mergeRecursive() {

        $arrays = func_get_args();
        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            reset($base);
            while (list($key, $value) = @each($array)) {
                if (is_array($value) && @is_array($base[$key])) {
                    $base[$key] = $this->mergeRecursive($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }

    /**
     * Returns an array of possible tests in this extension
     * @return array
     */
    public function getTests()
    {
        return array(
            new \Twig_SimpleTest('multiple', array($this, 'isMultipleTest'))
        );
    }

    /**
     * Returns content type with given name
     * @param $name string
     * @return ContentTypeInterface
     */
    public function getTypeFunction($name)
    {
        return $this->contentTypeManager->get($name);
    }

    /**
     * Return true if property is an array and needs an add button
     * @param $property PropertyInterface
     * @return bool
     */
    public function needsAddButtonFunction(PropertyInterface $property)
    {
        return $property->getMaxOccurs() > $property->getMinOccurs();
    }

    /**
     * Return if property is an array
     * @param $property PropertyInterface
     * @return bool
     */
    public function isMultipleTest($property)
    {
        return $property->getMinOccurs() > 1;
    }

    /**
     * Returns the name of the extension.
     * @return string The extension name
     */
    public function getName()
    {
        return 'content';
    }
}

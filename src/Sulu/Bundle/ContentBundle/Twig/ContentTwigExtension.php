<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Twig;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

/**
 * Extension for content form generation.
 */
class ContentTwigExtension extends \Twig_Extension
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
     * Returns an array of possible function in this extension.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_get_type', [$this, 'getTypeFunction']),
            new \Twig_SimpleFunction('sulu_needs_add_button', [$this, 'needsAddButtonFunction']),
            new \Twig_SimpleFunction('sulu_get_params', [$this, 'getParamsFunction']),
            new \Twig_SimpleFunction('sulu_parameter_to_select', [$this, 'convertParameterToSelect']),
            new \Twig_SimpleFunction('sulu_parameter_to_key_value', [$this, 'convertParameterToKeyValue']),
            new \Twig_SimpleFunction('sulu_parameter_to_names_array', [$this, 'convertParameterToNamesArrays']),
        ];
    }

    /**
     * Returns parameters for given property merged wit default parameters.
     *
     * @param PropertyInterface $property
     *
     * @return array
     */
    public function getParamsFunction(PropertyInterface $property)
    {
        $typeParams = [];
        if ($this->contentTypeManager->has($property->getContentTypeName())) {
            $type = $this->getTypeFunction($property->getContentTypeName());
            $typeParams = $type->getDefaultParams($property);
        }

        return $this->mergeRecursive($typeParams, $property->getParams());
    }

    /**
     * Better array merge recursive function
     *  - does not combine to scalar values to a array.
     *
     * @see http://php.net/manual/de/function.array-merge-recursive.php#106985
     *
     * @return array
     */
    private function mergeRecursive()
    {
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
     * Returns an array of possible tests in this extension.
     *
     * @return array
     */
    public function getTests()
    {
        return [
            new \Twig_SimpleTest('multiple', [$this, 'isMultipleTest']),
        ];
    }

    /**
     * Returns content type with given name.
     *
     * @param $name string
     *
     * @return ContentTypeInterface
     */
    public function getTypeFunction($name)
    {
        return $this->contentTypeManager->get($name);
    }

    /**
     * Return true if property is an array and needs an add button.
     *
     * @param $property PropertyInterface
     *
     * @return bool
     */
    public function needsAddButtonFunction(PropertyInterface $property)
    {
        $minOccurs = $property->getMinOccurs();
        $maxOccurs = $property->getMaxOccurs();

        if (is_null($maxOccurs) && $minOccurs >= 1) {
            return true;
        }

        return $maxOccurs > $minOccurs;
    }

    /**
     * Return if property is an array.
     *
     * @param $property PropertyInterface
     *
     * @return bool
     */
    public function isMultipleTest($property)
    {
        return $property->getIsMultiple();
    }

    /**
     * @param PropertyParameter[] $parameters
     * @param string $locale
     *
     * @return array
     */
    public function convertParameterToSelect($parameters, $locale)
    {
        $result = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->hasTitle($locale) ? $parameter->getTitle($locale) : $parameter->getValue();
            $result[] = [
                'id' => $parameter->getName(),
                'name' => $name,
            ];
        }

        return $result;
    }

    /**
     * @param PropertyParameter[] $parameters
     *
     * @return array
     */
    public function convertParameterToKeyValue($parameters)
    {
        $result = [];

        if (is_array($parameters)) {
            foreach ($parameters as $parameter) {
                $result[$parameter->getName()] = $parameter->getValue();
            }
        } else {
            return $parameters;
        }

        return $result;
    }

    /**
     * @param PropertyParameter[] $parameters
     *
     * @return array
     */
    public function convertParameterToNamesArrays($parameters)
    {
        $result = [];

        if (is_array($parameters)) {
            foreach ($parameters as $parameter) {
                $result[] = $parameter->getName();
            }
        } else {
            return $parameters;
        }

        return $result;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'content';
    }
}

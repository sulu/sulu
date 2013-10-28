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


use Sulu\Bundle\ContentBundle\Mapper\ContentMapper;

/**
 * Extension for content form generation
 *
 * @package Sulu\Bundle\ContentBundle\Twig
 */
class ContentExtension extends \Twig_Extension
{
    /**
     * Returns an array of possible filters in this extension
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('propertyDefaults', array($this, 'propertyDefaultsFilter')),
            new \Twig_SimpleFilter('isMultiple', array($this, 'isMultipleFilter'))
        );
    }

    /**
     * Returns an array of possible function in this extension
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('needsAddButton', array($this, 'needsAddButtonFunction'))
        );
    }

    /**
     * Return true if property is an array and needs an add button
     *
     * @param $property array
     * @return bool
     */
    public function needsAddButtonFunction($property)
    {
        return $property['maxOccurs'] > $property['minOccurs'];
    }

    /**
     * Returns property merged with default values
     *
     * @param $property array
     * @return array
     */
    public function propertyDefaultsFilter($property)
    {
        $property['type'] = $this->getType($property['type']);
        $defaults = array(
            'id' => $property['name'],
            'mandatory' => false,
            'minOccurs' => 1,
            'maxOccurs' => (isset($property['minOccurs']) ? $property['minOccurs'] : 1),
            'params' => $this->getParams($property['type'], $property)
        );

        return array_merge($defaults, $property);
    }

    /**
     * Return if property is an array
     *
     * @param $property array
     * @return bool
     */
    public function isMultipleFilter($property)
    {
        return $property['minOccurs'] > 1;
    }

    /**
     * Returns type default params merged with property params
     *
     * @param $type array
     * @param $property array
     * @return array
     */
    private function getParams($type, $property)
    {
        // TODO merge with property params
        return (isset($type['params']) ? $type['param'] : array());
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

    /**
     * Returns the type array for key
     *
     * @param $key string
     * @return mixed
     */
    private function getType($key)
    {
        // TODO get Types
        // perhaps? $this->get('content.parser.types')->get()[$key];
        return ContentMapper::$types[$key];
    }
}

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
            new \Twig_SimpleFilter('isArray', array($this, 'isArrayFilter'))
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('needsAddButton', array($this, 'needsAddButtonFunction'))
        );
    }


    public function needsAddButtonFunction($property)
    {
        return $property['maxOccurs'] > $property['minOccurs'];
    }

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

    public function isArrayFilter($property)
    {
        return $property['minOccurs'] > 1;
    }

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

    private function getType($key)
    {
        // TODO get Types
        // perhaps? $this->get('content.parser.types')->get()[$key];
        return ContentMapper::$types[$key];
    }
}

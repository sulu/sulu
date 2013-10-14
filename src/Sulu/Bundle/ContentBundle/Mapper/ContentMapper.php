<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Mapper;

abstract class ContentMapper implements ContentMapperInterface
{
    private $types = array(
        'textLine' => array(
            'name' => 'textLine',
            'phpcr-type' => 'string'
        ),
        'textArea' => array(
            'name' => 'textLine',
            'phpcr-type' => 'string'
        ),
        'richTextEditor' => array(
            'name' => 'textLine',
            'phpcr-type' => 'string'
        ),
        'image' => array(
            'name' => 'image',
            'phpcr-type' => 'reference'
        ),
        'document' => array(
            'name' => 'document',
            'phpcr-type' => 'reference'
        ),
        'internalLink' => array(
            'name' => 'internalLink',
            'phpcr-type' => 'reference'
        ),
        'resourceLocator' => array(
            'name' => 'resourceLocator'
        ),
        'smartContentSelection' => array(
            'name' => 'smartContentSelection'
        ),
        'imageSelection' => array(
            'name' => 'smartContentSelection',
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'textLine'
                ),
                'images' => array(
                    'name' => 'images',
                    'type' => 'image',
                    'minOccurs' => 1,
                    'maxOccurs' => 10
                ),

            )
        ),
        'documentSelection' => array(
            'name' => 'documentSelection',
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'textLine'
                ),
                'images' => array(
                    'name' => 'documents',
                    'type' => 'document',
                    'minOccurs' => 1,
                    'maxOccurs' => 10
                ),

            )
        ),
        'internalLinkSelection' => array(
            'name' => 'documentSelection',
            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'textLine'
                ),
                'images' => array(
                    'name' => 'internalLinks',
                    'type' => 'internalLink',
                    'minOccurs' => 1,
                    'maxOccurs' => 10
                ),

            )
        )
    );

    /**
     * reads the template file and returns a representative array
     *
     * @param $file string (not used yet)
     * @return array
     */
    protected function readTemplate($file)
    {
        $result = array(
            'key' => 'overview',
            'view' => 'page.html.twig',
            'controller' => 'SuluContentBundle:Default:index',
            'cacheLifeTime' => 2400,

            'properties' => array(
                'title' => array(
                    'name' => 'title',
                    'type' => 'textLine',
                    'mandatory' => true
                ),
                'url' => array(
                    'name' => 'url',
                    'type' => 'resourceLocator',
                    'mandatory' => true
                ),
                'article' => array(
                    'name' => 'article',
                    'type' => 'textArea',
                    'mandatory' => false
                )
            )
        );

        return $result;
    }

    /**
     * returns the type with given name. the params are will override default params of type
     *
     * @param $name string
     * @param $params string overridden params from template
     * @return array type with given params
     */
    protected function getType($name, $params)
    {
        return $this->types[$name];
    }

    /**
     * returns all types
     *
     * @return array all types
     */
    protected function getTypes()
    {
        return $this->types;
    }

}

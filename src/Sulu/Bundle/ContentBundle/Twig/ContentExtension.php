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
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\PropertyInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extension for content form generation
 * @package Sulu\Bundle\ContentBundle\Twig
 */
class ContentExtension extends \Twig_Extension implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Returns an array of possible function in this extension
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('getType', array($this, 'getTypeFunction')),
            new \Twig_SimpleFunction('needsAddButton', array($this, 'needsAddButtonFunction'))
        );
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
        return $this->container->get('sulu.content.type.' . $name);
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
    public function isMultipleTest( $property)
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

    /**
     * Sets the Container.
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;


use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StructureManager extends ContainerAware implements StructureManagerInterface
{
    /**
     * @var \Symfony\Component\Config\Loader\LoaderInterface XML Loader to load templates
     */
    private $loader;
    /**
     * @var string path to templates
     */
    private $defaultPath;

    /**
     * @param LoaderInterface $loader XMLLoader to load xml templates
     * @param string $defaultPath array with paths to search for templates
     */
    function __construct(LoaderInterface $loader, $defaultPath)
    {
        $this->loader = $loader;
        $this->defaultPath = $defaultPath;
    }

    /**
     * returns a structure for given key
     * @param $key string
     * @return mixed
     */
    public function getStructure($key)
    {
        $result = $this->loader->load('');
        // TODO: Implement getStructure() method.
    }
}

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
    private $loader;

    function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
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

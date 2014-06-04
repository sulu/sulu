<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager;


class DefaultCommandManager implements ManagerInterface
{

    /**
     * @var string The prefix to load the content from
     */
    private $prefix;

    /**
     * @param ContainerInterface $container
     * @param string $prefix
     */
    public function __construct(ContainerInterface $container, $prefix)
    {
        $this->setContainer($container);
        $this->prefix = $prefix;
    }

    /**
     * @param string $contentTypeName A String with the name of the content to load
     * @return ContentTypeInterface
     */
    public function get($contentTypeName = '')
    {
        return $this->container->get($this->prefix . $contentTypeName);
    }
} 

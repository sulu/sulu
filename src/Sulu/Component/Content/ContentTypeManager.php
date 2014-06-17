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

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * manages content types
 * @package Sulu\Component\Content
 */
class ContentTypeManager extends ContainerAware implements ContentTypeManagerInterface
{

    /**
     * @var string The prefix to load the content from
     * Default value is given in configuration and set to: 'sulu.content.types.'
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
     * {@inheritdoc}
     */
    public function get($contentTypeName)
    {
        return $this->container->get($this->prefix . $contentTypeName);
    }

    /**
     * {@inheritdoc}
     */
    public function has($contentTypeName)
    {
        return $this->container->has($this->prefix . $contentTypeName);
    }
}

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

use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\CommandInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Default implementation of command manager.
 */
class CommandManager extends ContainerAware implements ManagerInterface
{
    /**
     * @var string The prefix to load the image command
     */
    private $prefix;

    /**
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $imageCommandName A String with the name of the image command to load
     *
     * @return CommandInterface
     */
    public function get($imageCommandName = '')
    {
        return $this->container->get($this->prefix . $imageCommandName);
    }
}

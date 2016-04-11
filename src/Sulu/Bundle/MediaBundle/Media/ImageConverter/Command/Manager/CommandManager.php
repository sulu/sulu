<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager;

use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\CommandInterface;

/**
 * Default implementation of command manager.
 */
class CommandManager implements ManagerInterface
{
    /**
     * @var CommandInterface[]
     */
    private $commands = [];

    /**
     * @param CommandInterface $command
     * @param string           $alias
     */
    public function add(CommandInterface $command, $alias)
    {
        $this->commands[$alias] = $command;
    }

    /**
     * @param string $name A String with the name of the image command to load
     *
     * @return CommandInterface
     *
     * @throws \InvalidArgumentException If the command doesn't exist
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->commands)) {
            return $this->commands[$name];
        }

        throw new \InvalidArgumentException(sprintf(
            'A image converter command named "%s" does not exist.',
            $name
        ));
    }
}

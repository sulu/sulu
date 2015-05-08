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

use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyCommandNotFoundException;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Default implementation of command manager
 */
class CommandManager extends ContainerAware implements ManagerInterface
{
    /**
     * @var array
     */
    private $commandServices = array();

    /**
     * @var array
     */
    private $service = array();

    /**
     * @param array $commandServices
     */
    public function __construct(
        $commandServices = array()
    ) {
        $this->commandServices = $commandServices;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name = '')
    {
        if (!isset($this->service[$name])) {
            if (!isset($this->commandServices[$name])) {
                throw new ImageProxyCommandNotFoundException(sprintf('Service for "%s" was not found', $name));
            }
            $this->service[$name] = $this->container->get($this->commandServices[$name]);
        }

        return $this->service[$name];
    }
}

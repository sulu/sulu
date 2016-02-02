<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Initializer
{
    private $container;
    private $initializerMap;

    public function __construct(ContainerInterface $container, array $initializerMap = [])
    {
        $this->container = $container;
        $this->initializerMap = $initializerMap;
    }

    public function initialize(OutputInterface $output = null)
    {
        $output = $output ?: new NullOutput();
        arsort($this->initializerMap);

        foreach (array_keys($this->initializerMap) as $initializerId) {
            $initializer = $this->container->get($initializerId);
            $initializer->initialize($output);
        }
    }
}

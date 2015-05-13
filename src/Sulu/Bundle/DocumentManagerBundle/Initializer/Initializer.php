<?php

namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Initializer
{
    private $container;
    private $initializerMap;

    public function __construct(ContainerInterface $container, array $initializerMap = array())
    {
        $this->container = $container;
        $this->initializerMap = $initializerMap;
    }

    public function initialize(OutputInterface $output = null)
    {
        $output = $output ? : new NullOutput();
        arsort($this->initializerMap);

        foreach (array_keys($this->initializerMap) as $initializerId) {
            $initializer = $this->container->get($initializerId);
            $initializer->initialize($output);
        }
    }
}

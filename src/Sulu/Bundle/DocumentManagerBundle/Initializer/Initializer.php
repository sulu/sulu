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

/**
 * Content repository initializer.
 *
 * Can purge and initialize the content repository using any number of
 * registered *initializers*.
 *
 * Initializers should be designed to be idempotent - they should not do
 * anything to change the state of the repository beyond their initial changes:
 * https://en.wikipedia.org/wiki/Idempotent
 */
class Initializer
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $initializerMap;

    public function __construct(ContainerInterface $container, array $initializerMap = [])
    {
        $this->container = $container;
        $this->initializerMap = $initializerMap;
    }

    /**
     * Initialize the content repository, optionally purging it before-hand.
     *
     * @param OutputInterface $output
     * @param bool $purge
     */
    public function initialize(OutputInterface $output = null, $purge = false)
    {
        $output = $output ?: new NullOutput();

        arsort($this->initializerMap);

        foreach (array_keys($this->initializerMap) as $initializerId) {
            $output->writeln(sprintf('<comment>%s</>', $initializerId));
            $initializer = $this->container->get($initializerId);
            $initializer->initialize($output, $purge);
        }
        $output->write(PHP_EOL);
        $output->writeln('<comment>*</> Legend: [+] Added [*] Updated [-] Purged [ ] No change');
    }
}

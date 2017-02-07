<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SystemCollections;

use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Massive\Bundle\BuildBundle\Build\BuilderInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Build task to initialize system collections.
 */
class SystemCollectionBuilder implements BuilderInterface, ContainerAwareInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var SystemCollectionManagerInterface
     */
    private $systemCollectionManager;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'system_collections';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['cache', 'database', 'fixtures'];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $this->systemCollectionManager = $this->container->get('sulu_media.system_collections.manager');
        $this->systemCollectionManager->warmUp();
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(BuilderContext $context)
    {
        $this->output = $context->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}

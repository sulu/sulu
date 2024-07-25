<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SystemCollections;

use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Massive\Bundle\BuildBundle\Build\BuilderInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build task to initialize system collections.
 */
class SystemCollectionBuilder implements BuilderInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(private SystemCollectionManagerInterface $systemCollectionManager)
    {
    }

    public function getName()
    {
        return 'system_collections';
    }

    public function getDependencies()
    {
        return ['database', 'fixtures'];
    }

    public function build()
    {
        $this->systemCollectionManager->warmUp();
    }

    public function setContext(BuilderContext $context)
    {
        $this->output = $context->getOutput();
    }
}

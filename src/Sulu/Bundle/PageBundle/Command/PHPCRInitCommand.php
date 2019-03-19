<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Command;

use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * initiate phpcr repository (namespaces, nodetypes).
 *
 * @deprecated use sulu:document:initialize instead
 */
class PHPCRInitCommand extends Command
{
    /**
     * @var InitializerInterface
     */
    private $contentInitializer;

    public function __construct(InitializerInterface $contentInitializer)
    {
        $this->contentInitializer = $contentInitializer;
        parent::__construct('sulu:phpcr:init');
    }

    protected function configure()
    {
        $this->addOption('clear', 'c', InputOption::VALUE_OPTIONAL, '', false)
            ->setDescription('initiate phpcr repository (deprecated)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            $this->getHelper('formatter')->formatBlock(
                'DEPRECATED: This command is deprecated. use sulu:document:initialize instead',
                'comment',
                true
            )
        );
        $this->contentInitializer->initialize($output);
    }
}

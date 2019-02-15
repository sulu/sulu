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
 * Creates default routes in PHPCR for webspaces.
 *
 * @deprecated Use the sulu:document:initialize command instead
 */
class WebspacesInitCommand extends Command
{
    /**
     * @var InitializerInterface
     */
    private $webspaceInitiliazer;

    public function __construct(InitializerInterface $webspaceInitiliazer)
    {
        $this->webspaceInitiliazer = $webspaceInitiliazer;
        parent::__construct('sulu:webspaces:init');
    }

    protected function configure()
    {
        $this->setName('sulu:webspaces:init')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, '', 1)
            ->setDescription('Creates default nodes in PHPCR for webspaces (deprecated)');
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
        $this->webspaceInitiliazer->initialize($output);
    }
}

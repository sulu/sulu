<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates default routes in PHPCR for webspaces.
 *
 * @deprecated Use the sulu:document:initialize command instead
 */
class WebspacesInitCommand extends ContainerAwareCommand
{
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
        $this->getContainer()->get('sulu_core.webspace.document_manager.webspace_initializer')->initialize($output);
    }
}

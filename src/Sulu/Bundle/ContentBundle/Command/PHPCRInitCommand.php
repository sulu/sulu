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
 * initiate phpcr repository (namespaces, nodetypes).
 *
 * @deprecated use sulu:document:initialize instead
 */
class PHPCRInitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:phpcr:init')
            ->addOption('clear', 'c', InputOption::VALUE_OPTIONAL, '', false)
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
        $this->getContainer()->get('sulu_content.document_manager.content_initializer')->initialize($output);
    }
}

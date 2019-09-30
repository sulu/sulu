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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ContentTypesDumpCommand extends Command
{
    protected static $defaultName = 'sulu:content:types:dump';

    protected function configure()
    {
        $this->setDescription('Dumps all ContentTypes registered in the system')
            ->setDefinition([
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('debug:container');

        $arguments = [
            'command' => 'debug:container',
            '--tag' => 'sulu.content.type',
            '--format' => $input->getOption('format'),
        ];

        return $command->run(new ArrayInput($arguments), $output);
    }
}

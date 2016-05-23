<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ServerStatusCommand as BaseServerStatusCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStatusCommand extends BaseServerStatusCommand
{
    use DetermineServerInfoTrait;

    protected function configure()
    {
        parent::configure();

        $this->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Address port number');

        $this->getDefinition()->getArgument('address')->setDefault('127.0.0.1');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $this->getContainer()->getParameter('sulu.context');

        $port = $this->determinePort($input->getOption('port'), $context);

        $address = $input->getArgument('address');

        if (false === strpos($address, ':')) {
            $address = $address . ':' . $port;
        }

        $input->setOption('port', $port);
        $input->setArgument('address', $address);

        return parent::execute($input, $output);
    }
}

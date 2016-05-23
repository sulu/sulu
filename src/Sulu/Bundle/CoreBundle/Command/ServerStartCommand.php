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

use Symfony\Bundle\FrameworkBundle\Command\ServerStartCommand as BaseServerStartCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ServerStartCommand extends BaseServerStartCommand
{
    use DetermineServerInfoTrait;

    protected function configure()
    {
        parent::configure();

        // We'll determine the port dynamically
        $this->getDefinition()->getOption('port')->setDefault(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $this->getContainer()->getParameter('sulu.context');
        $io = new SymfonyStyle($input, $cliOutput = $output);

        if (false === $router = $this->determineRouterScript($input->getOption('router'), $context, $io)) {
            return 1;
        }

        $port = $this->determinePort($input->getOption('port'), $context);

        $input->setOption('router', $router);
        $input->setOption('port', $port);

        return parent::execute($input, $output);
    }
}

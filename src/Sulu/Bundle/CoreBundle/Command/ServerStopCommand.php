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

use Symfony\Bundle\FrameworkBundle\Command\ServerStopCommand as BaseServerStopCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStopCommand extends BaseServerStopCommand
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

        $port = $this->determinePort($input->getOption('port'), $context);

        $input->setOption('port', $port);

        return parent::execute($input, $output);
    }
}

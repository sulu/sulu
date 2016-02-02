<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that starts the websocket server.
 */
class RunWebsocketCommand extends ContainerAwareCommand
{
    /**
     * Service id of websocket manager.
     */
    const MANAGER_ID = 'sulu_websocket.manager';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:websocket:run');
        $this->setDescription('Start websocket server');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get(self::MANAGER_ID);

        $output->writeln(
            sprintf(
                'Websocket server started: "ws://%s:%s/<route>" bound to IP %s',
                $manager->getHttpHost(),
                $manager->getPort(),
                $manager->getIpAddress()
            )
        );

        $manager->run();
    }
}

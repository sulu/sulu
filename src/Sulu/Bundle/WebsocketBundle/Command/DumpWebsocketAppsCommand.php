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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that dump the websocket app and config.
 */
class DumpWebsocketAppsCommand extends ContainerAwareCommand
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
        $this->setName('sulu:websocket:dump');
        $this->setDescription('Dumps websocket apps config');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get(self::MANAGER_ID);

        /** @var Table $table */
        $table = $this->getHelper('table');
        $table->setHeaders(['App-Name', 'Route', 'Allowed-Origins', 'Host-Name']);

        foreach ($manager->getApps() as $app) {
            $table->addRow(
                [
                    $app['name'],
                    $app['route'],
                    print_r($app['allowedOrigins'], true),
                    $manager->getHttpHost(),
                ]
            );
        }

        $table->render($output);
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PreviewServerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:content:preview:start')
            ->setDescription('Start preview Server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var MessageComponentInterface $previewMessageComponent */
        $previewMessageComponent = $this->getMessageComponent();
        $server = IoServer::factory($previewMessageComponent, 8080);
        $server->run();
    }

    private function getMessageComponent()
    {
        return $this->getContainer()->get('sulu_content.preview.message_component');
    }
} 

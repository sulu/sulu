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

use Sulu\Component\Content\ContentTypeManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps all content types to console.
 */
class ContentTypesDumpCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:content:types:dump')
            ->setDescription('Dumps all ContentTypes registered in the system');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ContentTypeManagerInterface $contentTypeManager */
        $contentTypeManager = $this->getContainer()->get('sulu.content.type_manager');

        $table = $this->getHelper('table');
        $table->setHeaders(['Alias', 'Service ID']);

        foreach ($contentTypeManager->getAll() as $alias => $service) {
            $table->addRow([$alias, $service['id']]);
        }
        $table->render($output);
    }
}

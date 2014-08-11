<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use Sulu\Component\Content\ContentTypeManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps all content types to console
 * @package Sulu\Bundle\CoreBundle\Command
 */
class ContentTypeDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:content:debug')
            ->setDescription('Dumps all ContentType´s in system');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ContentTypeManagerInterface $contentTypeManager */
        $contentTypeManager = $this->getContainer()->get('sulu.content.type_manager');

        $table = $this->getHelper('table');
        $table->setHeaders(array('Alias', 'Service ID'));

        foreach ($contentTypeManager->getAll() as $alias => $service) {
            $table->addRow(array($alias, $service['id']));
            $output->writeln($alias);
        }
        $table->render($output);
   }

} 

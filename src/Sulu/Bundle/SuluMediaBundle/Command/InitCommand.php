<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Sulu\Bundle\MediaBundle\Command
 */
class InitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:media:init')
            ->setDescription('Init Sulu Media Bundle');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseDir = dirname(dirname($this->getContainer()->get('kernel')->getRootDir()));

        $output->writeln('Create upload dir ...');

        $uploadDir = $baseDir . '/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir);
        } else {
            $output->writeLn('Directory ' . $uploadDir . ' already exists');
        }

        $suluMediaDir = $uploadDir . '/sulumedia';

        if (!is_dir($suluMediaDir)) {
            mkdir($suluMediaDir);
        } else {
            $output->writeLn('Directory ' . $suluMediaDir . ' already exists');
        }
    }
}

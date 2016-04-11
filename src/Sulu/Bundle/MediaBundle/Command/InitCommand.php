<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class InitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:media:init')
            ->setDescription('Init Sulu Media Bundle');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseDir = dirname($this->getContainer()->get('kernel')->getRootDir());

        /** @var Filesystem $filesystem */
        $filesystem = $this->getContainer()->get('filesystem');

        $output->writeln('Create media dirs in ' . $baseDir);

        $uploadDir = $this->getContainer()->getParameter('sulu_media.media.storage.local.path');

        $output->writeln('Create Upload dir in ' . $uploadDir);

        if (!is_dir($uploadDir)) {
            $filesystem->mkdir($uploadDir);
        } else {
            $output->writeLn('Directory ' . $uploadDir . ' already exists');
        }

        $mediaCacheDir = $this->getContainer()->getParameter('sulu_media.format_cache.path');

        $output->writeln('Create Media Cache dir in ' . $mediaCacheDir);

        if (!is_dir($mediaCacheDir)) {
            $filesystem->mkdir($mediaCacheDir);
        } else {
            $output->writeLn('Directory ' . $mediaCacheDir . ' already exists');
        }
    }
}

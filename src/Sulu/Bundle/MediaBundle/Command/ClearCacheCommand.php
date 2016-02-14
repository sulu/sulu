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

use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheClearerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears the media cache.
 */
class ClearCacheCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sulu:media:format:cache:clear')
            ->setDescription('Clear all or the given Sulu media format cache')
            ->addArgument('cache', InputArgument::OPTIONAL, 'Optional alias to clear the specific cache')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FormatCacheClearerInterface $cacheClearer */
        $cacheClearer = $this->getContainer()->get('sulu_media.format_cache_clearer');
        $cache = $input->getArgument('cache');

        $output->writeln('Clearing the Sulu media format cache.');
        $cacheClearer->clear($cache);
    }
}

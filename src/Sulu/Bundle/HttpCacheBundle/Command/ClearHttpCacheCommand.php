<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Command;

use Sulu\Bundle\WebsiteBundle\Cache\CacheClearerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearHttpCacheCommand extends Command
{
    protected static $defaultName = 'sulu:http-cache:clear';

    /**
     * @var CacheClearerInterface
     */
    private $cacheClearer;

    public function __construct(CacheClearerInterface $cacheClearer)
    {
        parent::__construct(self::$defaultName);

        $this->cacheClearer = $cacheClearer;
    }

    protected function configure()
    {
        $this->setDescription('Clear HTTP-Cache.');
        $this->setHelp(
            <<<'EOT'
The <info>%command.name%</info> command clears the whole http-cache.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cacheClearer->clear();

        $io = new SymfonyStyle($input, $output);
        $io->success('HTTP-Cache cleared successfully');
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class InitCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $formatCacheDir;

    public function __construct(Filesystem $filesystem, string $formatCacheDir)
    {
        $this->filesystem = $filesystem;
        $this->formatCacheDir = $formatCacheDir;
        parent::__construct('sulu:media:init');
    }

    protected function configure()
    {
        $this->setDescription('Init Sulu Media Bundle');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Create Media Cache dir in ' . $this->formatCacheDir);

        if (!is_dir($this->formatCacheDir)) {
            $this->filesystem->mkdir($this->formatCacheDir);
        } else {
            $output->writeLn('Directory "' . $this->formatCacheDir . '"" already exists');
        }
    }
}

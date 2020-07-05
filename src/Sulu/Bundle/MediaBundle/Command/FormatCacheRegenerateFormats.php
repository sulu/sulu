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

use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheClearerInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FormatCacheRegenerateFormats extends Command
{
    protected static $defaultName = 'sulu:media:regenerate:formats';

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var FormatManagerInterface
     */
    private $formatManager;

    /**
     * @var string
     */
    private $localFormatCachePath;

    public function __construct(
        Filesystem $filesystem,
        FormatManagerInterface $formatManager,
        string $localFormatCachePath
    ) {
        parent::__construct();

        $this->fileSystem = $filesystem;
        $this->formatManager = $formatManager;
        $this->localFormatCachePath = $localFormatCachePath;
    }

    protected function configure()
    {
        $this->setDescription('Loops over sulu image cache, and regenerates the existing images')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        $finder = new Finder();
        $finder->in(\realpath($this->localFormatCachePath));
        $files = $finder->files();

        $progressBar = $ui->createProgressBar(\count($files));

        if (!count($files)) {
            $ui->writeln('No images to regenerate');
            return 0;
        }

        $ui->writeln('Starting to regenerate: ' . count($files) .' images');
        $ui->writeln('');

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileInformation = $this->getFileInformationArrayFromPath($file->getRelativePathname());

            $this->formatManager->returnImage(
                $fileInformation['id'],
                $fileInformation['formatKey'],
                $fileInformation['fileName']
            );

            $progressBar->advance();
        }

        $ui->writeln('');
        $ui->writeln('');
        $ui->writeln('DONE');

        $progressBar->finish();

        return 0;
    }

    private function getFileInformationArrayFromPath($path): array
    {
        $exploded = explode('/', $path);

        return [
            'id' => $exploded[1],
            'formatKey' => $exploded[0],
            'fileName' => $exploded[2]
        ];
    }
}

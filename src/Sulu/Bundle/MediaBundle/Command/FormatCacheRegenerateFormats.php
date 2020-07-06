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

use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FormatCacheRegenerateFormats extends Command
{
    protected static $defaultName = 'sulu:media:regenerate-formats';

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

        if (!\count($files)) {
            $ui->writeln(\sprintf('No images to regenerate found in "%s".', $this->localFormatCachePath));

            return 0;
        }

        $progressBar = $ui->createProgressBar(\count($files));

        $ui->writeln('Starting to regenerate: ' . \count($files) . ' images');
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

        $progressBar->finish();

        $ui->writeln('');
        $ui->writeln('');

        $ui->success(\sprintf('Finished regenerating of "%s" images.', \count($files)));

        return 0;
    }

    private function getFileInformationArrayFromPath($path): array
    {
        $exploded = \explode('/', $path);

        $directories = \explode('/', $path, 2);
        $fileNameParts = \explode('-', $directories[1], 2);

        return [
            'id' => $fileNameParts[0],
            'formatKey' => $directories[0],
            'fileName' => $fileNameParts[1],
        ];
    }
}

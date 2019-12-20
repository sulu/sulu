<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DownloadLanguageCommand extends Command
{
    // TODO replace URL from local sulu-website to correct one
    const TRANSLATION_BASE_URL = 'http://127.0.0.1:8001/translation/';

    protected static $defaultName = 'sulu:admin:download-language';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $projectDir;

    public function __construct(HttpClientInterface $httpClient, string $projectDir)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->projectDir = $projectDir;
    }

    protected function configure()
    {
        $this->setDescription('Downloads the currently approved translations for the given language.')
            ->addArgument('language', InputArgument::REQUIRED, 'The language to download');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $language = $input->getArgument('language');

        $response = $this->httpClient->request(
            'GET',
            static::TRANSLATION_BASE_URL . 'sulu%2Fsulu/' . $language
        );

        $tempDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sulu-language-' . $language . DIRECTORY_SEPARATOR;
        $tempFile = $tempDirectory . 'download.zip';

        $filesystem = new Filesystem();
        $filesystem->mkdir($tempDirectory);

        file_put_contents($tempFile, $response->getContent());

        $zip = new \ZipArchive();
        if ($zip->open($tempFile)) {
            $output->writeln('<info>Extract ZIP archive...</info>');
            $zip->extractTo($tempDirectory);
            $zip->close();

            $output->writeln('<info>Copy language into translations folder</info>');
            $languageFile = 'admin.' . $language . '.json';
            $filesystem->copy(
                $tempDirectory . $languageFile,
                $this->projectDir . DIRECTORY_SEPARATOR . 'translations/sulu' . DIRECTORY_SEPARATOR . $languageFile
            );
        } else {
            $output->writeln('<error>Error when unpacking the ZIP archive</error>');
        }

        $filesystem->remove($tempDirectory);
    }
}

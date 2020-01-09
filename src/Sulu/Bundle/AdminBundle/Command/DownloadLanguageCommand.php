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
    const TRANSLATION_BASE_URL = 'https://translations.sulu.io/';

    protected static $defaultName = 'sulu:admin:download-language';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $projectDir;

    public function __construct(HttpClientInterface $httpClient, Filesystem $filesystem, string $projectDir)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->filesystem = $filesystem;
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

        $composerJson = json_decode(file_get_contents($this->projectDir . DIRECTORY_SEPARATOR . 'composer.json'), true);
        $packages = array_keys($composerJson['require']);
        $suluPackages = array_filter($packages, function($package) {
            return 0 === strpos($package, 'sulu/');
        });

        $translationsFile = $this->projectDir
            . DIRECTORY_SEPARATOR
            . 'translations/sulu'
            . DIRECTORY_SEPARATOR
            . 'admin.' . $language . '.json';

        if (file_exists($translationsFile)) {
            $translations = json_decode(file_get_contents($translationsFile), true);
        } else {
            $translations = [];
        }

        foreach ($suluPackages as $suluPackage) {
            $translations = array_merge($translations, $this->downloadLanguage($output, $language, $suluPackage));
        }

        $output->writeln('<info>Writing language into translations folder</info>');

        $this->filesystem->mkdir(dirname($translationsFile));

        $this->filesystem->dumpFile($translationsFile, json_encode($translations));
    }

    private function downloadLanguage($output, $language, $project): array
    {
        $output->writeln('<info>Starting download for the "' . $project . '" project in "' . $language . '"</info>');
        $response = $this->httpClient->request(
            'GET',
            static::TRANSLATION_BASE_URL . $project . '/' . $language
        );

        $tempDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sulu-language-' . $language . DIRECTORY_SEPARATOR;
        $tempFile = $tempDirectory . 'download.zip';

        $this->filesystem->mkdir($tempDirectory);

        if (404 === $response->getStatusCode()) {
            return [];
        }

        $this->filesystem->dumpFile($tempFile, $response->getContent());

        $translations = [];
        $zip = new \ZipArchive();
        if ($zip->open($tempFile)) {
            $output->writeln('<info>Extract ZIP archive...</info>');
            $zip->extractTo($tempDirectory);
            $zip->close();

            $languageFile = 'admin.' . $language . '.json';
            $translations = json_decode(file_get_contents($tempDirectory . $languageFile), true);
        } else {
            $output->writeln('<error>Error when unpacking the ZIP archive</error>');
        }

        $this->filesystem->remove($tempDirectory);

        return $translations;
    }
}

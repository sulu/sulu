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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DownloadLanguageCommand extends Command
{
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
            ->addArgument('languages', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'The language to download')
            ->addOption(
                'base-url',
                'b',
                InputOption::VALUE_REQUIRED,
                'The endpoint where the translation should be downloaded.',
                'https://translations.sulu.io/'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $languages = $input->getArgument('languages');

        foreach ($languages as $language) {
            $this->executeLanguage($input, $output, $language);
        }

        return 0;
    }

    protected function executeLanguage(InputInterface $input, OutputInterface $output, string $language): void
    {
        $translationBaseUrl = $input->getOption('base-url');

        $ui = new SymfonyStyle($input, $output);
        $ui->section('Language: ' . $language);

        if (in_array($language, ['en', 'de'])) {
            $ui->note(sprintf(
                'The language "%s" is core languages and don\'t need to be downloaded.',
                $language
            ));

            return;
        }

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

        $packages = [];
        foreach ($suluPackages as $suluPackage) {
            $packageTranslations = $this->downloadLanguage($output, $language, $suluPackage, $translationBaseUrl);
            $packages[$suluPackage] = [
                'Package' => $suluPackage,
                'Translations' => count($packageTranslations),
            ];
            $translations = array_merge($translations, $packageTranslations);
        }

        if (empty($translations)) {
            $ui->warning(sprintf('No translations found for language %s.', $language));

            return;
        }

        $ui->newLine();
        $ui->table(['Package', 'Translations', 'Error'], $packages);

        $output->writeln(sprintf('<info>Writing language %s into translations folder</info>', $language));

        $this->filesystem->mkdir(dirname($translationsFile));

        $this->filesystem->dumpFile($translationsFile, json_encode($translations));
    }

    private function downloadLanguage($output, $language, $project, $translationBaseUrl): array
    {
        $output->writeln('<info>Starting download for the "' . $project . '" project in "' . $language . '"</info>');
        $response = $this->httpClient->request(
            'GET',
            $translationBaseUrl . $project . '/' . $language
        );

        $tempDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sulu-language-' . $language . DIRECTORY_SEPARATOR;
        $tempFile = $tempDirectory . 'download.zip';

        $this->filesystem->mkdir($tempDirectory);

        if (!200 === $response->getStatusCode()) {
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

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DownloadBuildCommand extends Command
{
    const EXIT_CODE_INVALID_FILES = 1;
    const EXIT_CODE_COULD_NOT_INSTALL_NPM_PACKAGES = 2;
    const EXIT_CODE_COULD_NOT_BUILD_ADMIN_ASSETS = 3;

    protected static $defaultName = 'sulu:admin:download-build';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $suluVersion;

    /**
     * @var string
     */
    private $remoteRepository;

    /**
     * @var string
     */
    private $remoteArchive;

    const ASSETS_DIR = \DIRECTORY_SEPARATOR . 'assets' . \DIRECTORY_SEPARATOR . 'admin' . \DIRECTORY_SEPARATOR;

    const BUILD_DIR = \DIRECTORY_SEPARATOR . 'public' . \DIRECTORY_SEPARATOR . 'build' . \DIRECTORY_SEPARATOR . 'admin';

    const REPOSITORY_NAME = 'skeleton';

    const VERSION_REGEX = '/^\d+\.\d+\.\d+(-(alpha|beta|RC)\d+)?$/';

    public function __construct(HttpClientInterface $httpClient, string $projectDir, string $suluVersion)
    {
        parent::__construct();

        $this->httpClient = $httpClient;
        $this->projectDir = $projectDir;
        $this->suluVersion = $suluVersion;
        $this->remoteRepository = 'https://raw.githubusercontent.com/sulu/skeleton/' . $suluVersion;
        $this->remoteArchive = 'https://codeload.github.com/sulu/skeleton/zip/' . $suluVersion;
    }

    protected function configure()
    {
        $this->setDescription('Downloads the current admin application build from the sulu/skeleton repository.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!\preg_match(static::VERSION_REGEX, $this->suluVersion)) {
            throw new \Exception(
                'This command only works for tagged sulu versions matching semantic versioning, not for branches etc. '
                . 'Given version was "' . $this->suluVersion . '".' . \PHP_EOL
                . 'You would have to run "npm install" and "npm run build" in your "assets/admin" folder on your own.'
            );
        }

        $ui = new SymfonyStyle($input, $output);

        $output->writeln('<info>Checking for changed files...</info>');

        $assetFiles = [
            'index.js',
            'package.json',
            'webpack.config.js',
            '.babelrc',
        ];

        $invalidAssetsFiles = true;

        foreach ($assetFiles as $file) {
            $filePath = static::ASSETS_DIR . $file;
            $ui->section('Checking: ' . $filePath);
            $localContent = $this->getLocalFile($filePath);
            $remoteContent = $this->getRemoteFile($filePath);

            if ($this->hash($localContent) !== $this->hash($remoteContent)) {
                $ui->writeln('Differences between local and remote version of the file found:');
                $ui->writeln('');

                $ui->table(['Old Version', 'New Version'], [
                    [$localContent, $remoteContent],
                ]);

                if ('y' !== \strtolower($ui->ask('Do you want to use the new version?', 'y'))) {
                    $invalidAssetsFiles = true;

                    continue;
                }

                $ui->writeln(\sprintf('Update "%s" file.', $file));
                $this->writeFile($filePath, $remoteContent);
            }
        }

        if ($invalidAssetsFiles) {
            return $this->doManualBuild($ui);
        }

        $tempDirectory = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . static::REPOSITORY_NAME . \uniqid(\rand(), true);
        $tempFileZip = $tempDirectory . '.zip';

        $output->writeln('<info>Download remote repository...</info>');
        $response = $this->httpClient->request('GET', $this->remoteArchive);

        $filesystem = new Filesystem();

        \file_put_contents($tempFileZip, $response->getContent());

        $zip = new \ZipArchive();
        if ($zip->open($tempFileZip)) {
            $output->writeln('<info>Extract ZIP archive...</info>');
            $zip->extractTo($tempDirectory);
            $zip->close();

            $buildDir = $this->projectDir . static::BUILD_DIR;
            $extractedFolderName = static::REPOSITORY_NAME . '-' . $this->suluVersion;
            $tempProjectDir = $tempDirectory . \DIRECTORY_SEPARATOR . $extractedFolderName;

            $output->writeln('<info>Delete old build folder...</info>');
            $filesystem->remove(\glob($buildDir . \DIRECTORY_SEPARATOR . '*'));

            $output->writeln('<info>Copy build folder from remote repository...</info>');
            $filesystem->mirror(
                $tempProjectDir . static::BUILD_DIR,
                $buildDir
            );

            $filesystem->remove($tempDirectory);
        } else {
            $output->writeln('<error>Error when unpacking the ZIP archive</error>');
        }

        \unlink($tempFileZip);
    }

    private function getLocalFile(string $path)
    {
        if (!\file_exists($this->projectDir . $path)) {
            return '';
        }

        return \file_get_contents($this->projectDir . $path);
    }

    private function getRemoteFile(string $path)
    {
        $path = \str_replace(\DIRECTORY_SEPARATOR, '/', $path);
        $response = $this->httpClient->request('GET', $this->remoteRepository . $path);

        return $response->getContent();
    }

    private function hash($content)
    {
        // we remove all whitespaces as the developer could change the indention or/and the line breaks of this files
        return \hash('sha256', \preg_replace('/\s+/', '', $content));
    }

    private function writeFile(string $path, string $content): void
    {
        \file_put_contents($this->projectDir . $path, $content);
    }

    private function doManualBuild(SymfonyStyle $ui): int
    {
        $ui->warning(\sprintf(
            'The files in the local "%s" folder do not match the ones in the remote repository "%s".' . \PHP_EOL
            . 'Either bundles with custom JavaScript have been added, which means it has to be done manually '
            . 'with NPM. Do you want start a manually build?',
            static::ASSETS_DIR,
            $this->remoteRepository
        ));

        $ui->title('Start manual build ...');

        if ('y' !== \strtolower($ui->ask('Do you want start a manually build now?', 'y'))) {
            return self::EXIT_CODE_INVALID_FILES;
        }

        $ui->section('Clean up node modules folders');
        $this->cleanupNodeModulesFolders();

        $ui->section('Install npm dependencies');
        if ($this->runProcess($ui, 'npm install')) {
            $ui->error('Could not build correctly install npm dependendencies.');

            return self::EXIT_CODE_COULD_NOT_INSTALL_NPM_PACKAGES;
        }

        $ui->section('Build admin assets');
        if ($this->runProcess($ui, 'npm run build')) {
            $ui->error('Could not build correctly build admin assets.');

            return self::EXIT_CODE_COULD_NOT_BUILD_ADMIN_ASSETS;
        }

        return 0;
    }

    private function cleanupNodeModulesFolders(): void
    {
        $removeBlockingFiles = [
            'package-lock.json',
            'yarn.lock',
            'node_modules',
        ];

        $packageJson = \json_decode($this->getLocalFile(self::ASSETS_DIR . 'package.json'), true);

        if (!$packageJson) {
            throw new \Exception(\sprintf('Could not parse "%s" file', self::ASSETS_DIR . 'package.json'));
        }

        $nodeModulesFolders = [
            $this->projectDir . self::ASSETS_DIR,
        ];

        foreach ($packageJson['dependencies'] as $dependency => $path) {
            if (0 !== \strpos($path, 'file:')) {
                continue;
            }

            $nodeModulesFolders[] = $this->projectDir . self::ASSETS_DIR . \substr($path, \strlen('file:'));
        }

        $filesystem = new Filesystem();
        foreach ($nodeModulesFolders as $folder) {
            foreach ($removeBlockingFiles as $blockingFile) {
                $path = $folder . $blockingFile;

                if (!$filesystem->exists($path)) {
                    continue;
                }

                $filesystem->remove($path);
            }
        }
    }

    private function runProcess(SymfonyStyle $ui, $command): int
    {
        $process = Process::fromShellCommandline($command, $this->projectDir . self::ASSETS_DIR);
        $process->setTimeout(3600);
        $process->run(function($type, $buffer) use ($ui) {
            $ui->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });

        return $process->getExitCode();
    }
}

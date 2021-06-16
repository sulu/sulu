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

class UpdateBuildCommand extends Command
{
    const EXIT_CODE_ABORTED_MANUAL_BUILD = 1;
    const EXIT_CODE_COULD_NOT_INSTALL_NPM_PACKAGES = 2;
    const EXIT_CODE_COULD_NOT_BUILD_ADMIN_ASSETS = 3;

    protected static $defaultName = 'sulu:admin:update-build';

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
    private $suluVendorDir;

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

    /**
     * @var Filesystem
     */
    private $filesystem;

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

        $this->suluVendorDir = \dirname(__DIR__, 5);
        $this->remoteRepository = 'https://raw.githubusercontent.com/sulu/skeleton/' . $suluVersion;
        $this->remoteArchive = 'https://codeload.github.com/sulu/skeleton/zip/' . $suluVersion;
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this->setDescription(
            'Updates the administration application JavaScript build by downloading the official build '
            . 'from the sulu/skeleton repository or building the assets via npm.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        if (!\preg_match(static::VERSION_REGEX, $this->suluVersion)) {
            $ui->warning(
                'This command can only download the official build for tagged versions of the "sulu/sulu" '
                . 'package, not for branches etc. Your version is "' . $this->suluVersion . '".' . \PHP_EOL
                . 'When not using a tagged version, you need to create the JavaScript build by yourself. '
                . 'Please make sure that the content of your "assets/admin" folder is compatible with '
                . 'your "sulu/sulu" package.'
            );

            return $this->doManualBuild($ui);
        }

        $output->writeln('<info>Checking for changed files...</info>');

        $assetFiles = [
            'index.js',
            'package.json',
            'webpack.config.js',
            'babel.config.json',
            '.browserslistrc',
            '.npmrc',
            'postcss.config.js',
        ];

        $renamedFiles = [
            '.babelrc' => 'babel.config.json',
        ];

        foreach ($renamedFiles as $oldFile => $newFile) {
            if ($this->filesystem->exists($this->projectDir . $oldFile)) {
                if ('y' === \strtolower(
                    $ui->ask(\sprintf('The "%s" should be renamed to "%s" should wo do this now?', $oldFile, $newFile), 'y')
                )) {
                    $this->filesystem->rename($this->projectDir . $oldFile, $this->projectDir . $newFile);
                }
            }
        }

        $needManualBuild = false;

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

                if ('y' !== \strtolower(
                    $ui->ask(\sprintf('Do you want to overwrite your local version of "%s"?', $file), 'y')
                )) {
                    $needManualBuild = true;

                    if ($localContent && \in_array($file, ['package.json'])) {
                        $mergedJson = $this->mergeJsonStrings($localContent, $remoteContent);

                        $ui->writeln(\sprintf('Merged Version of "%s":', $file));
                        $ui->writeln($mergedJson);

                        if ('y' === \strtolower(
                            $ui->ask(
                                \sprintf('Merge "%s" together like above?', $file),
                                'y'
                            )
                        )) {
                            $ui->writeln(\sprintf('Write new "%s" version.', $file));
                            $this->writeFile($filePath, $mergedJson . "\n");
                        }
                    }

                    continue;
                }

                $ui->writeln(\sprintf('Overwriting "%s" with remote version.', $file));
                $this->writeFile($filePath, $remoteContent);
            }
        }

        if ($needManualBuild) {
            $ui->warning(\sprintf(
                'The files in the local "%s" folder do not match the ones in the remote repository "%s".' . \PHP_EOL
                . 'If you have added custom JavaScript to the administration interface, you need to create '
                . 'the JavaScript build by yourself.',
                static::ASSETS_DIR,
                $this->remoteRepository
            ));

            return $this->doManualBuild($ui);
        }

        $tempDirectory = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . static::REPOSITORY_NAME . \uniqid((string) \rand(), true);
        $tempFileZip = $tempDirectory . '.zip';

        $output->writeln('<info>Download remote repository...</info>');
        $response = $this->httpClient->request('GET', $this->remoteArchive);
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
            $this->filesystem->remove(\glob($buildDir . \DIRECTORY_SEPARATOR . '*'));

            $output->writeln('<info>Copy build folder from remote repository...</info>');
            $this->filesystem->mirror(
                $tempProjectDir . static::BUILD_DIR,
                $buildDir
            );

            $this->filesystem->remove($tempDirectory);
        } else {
            $output->writeln('<error>Error when unpacking the ZIP archive</error>');
        }

        \unlink($tempFileZip);

        return 0;
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

    private function mergeJsonStrings(string $mainJson, string $additionalJson): string
    {
        $mainJsonArray = \json_decode($mainJson, true);
        $additionalJsonArray = \json_decode($additionalJson, true);

        if (!$mainJsonArray) {
            throw new \RuntimeException(\sprintf('The following is not a valid json: ' . \PHP_EOL . '%s', $mainJson));
        }

        if (!$additionalJsonArray) {
            throw new \RuntimeException(\sprintf('The following is not a valid json: ' . \PHP_EOL . '%s', $additionalJson));
        }

        $jsonArray = \array_replace_recursive($mainJsonArray, $additionalJsonArray);

        return \json_encode($jsonArray, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    private function doManualBuild(SymfonyStyle $ui): int
    {
        if ('y' !== \strtolower($ui->ask('Do you want to create a build now?', 'y'))) {
            return static::EXIT_CODE_ABORTED_MANUAL_BUILD;
        }

        $conflictingDirectories = [];
        foreach ([
             $this->projectDir . '/node_modules',
             $this->suluVendorDir . '/node_modules',
        ] as $conflictingDirectory) {
            $renamedConflictingDirectoryName = $conflictingDirectory . '.bak';
            if ($this->filesystem->exists($conflictingDirectory)) {
                $ui->writeln(\sprintf('Renaming directory "%s" to prevent webpack resolution conflicts during build', $conflictingDirectory));
                $this->filesystem->rename($conflictingDirectory, $renamedConflictingDirectoryName);
                $conflictingDirectories[$conflictingDirectory] = $renamedConflictingDirectoryName;
            } elseif ($this->filesystem->exists($renamedConflictingDirectoryName)) {
                // add also previously renamed directory from a manual cancelled process
                $conflictingDirectories[$conflictingDirectory] = $renamedConflictingDirectoryName;
            }
        }

        try {
            $ui->title('Start manual build ...');

            $ui->section('Cleanup previously installed "node_modules" folders');
            $this->cleanupPreviouslyInstalledDependencies();

            $ui->section('Install npm dependencies');
            if ($this->runProcess($ui, 'npm install')) {
                $ui->error('Unexpected error while installing npm dependencies.');

                return static::EXIT_CODE_COULD_NOT_INSTALL_NPM_PACKAGES;
            }

            $ui->section('Build administration interface assets');
            if ($this->runProcess($ui, 'npm run build')) {
                $ui->error('Unexpected error while building administration interface assets.');

                return static::EXIT_CODE_COULD_NOT_BUILD_ADMIN_ASSETS;
            }
        } finally {
            foreach ($conflictingDirectories as $conflictingDirectory => $renamedConflictingDirectoryName) {
                $ui->writeln(\sprintf('Restore original name of renamed directory "%s"', $conflictingDirectory));
                $this->filesystem->rename($renamedConflictingDirectoryName, $conflictingDirectory);
            }
        }

        return 0;
    }

    private function cleanupPreviouslyInstalledDependencies(): void
    {
        $filesToCleanup = [
            'package-lock.json',
            'yarn.lock',
            'node_modules',
        ];

        $packageJson = \json_decode($this->getLocalFile(static::ASSETS_DIR . 'package.json'), true);

        if (!$packageJson) {
            throw new \Exception(\sprintf('Could not parse "%s" file', static::ASSETS_DIR . 'package.json'));
        }

        $npmPackageFolders = [
            $this->projectDir . static::ASSETS_DIR,
            $this->suluVendorDir,
        ];

        foreach ($packageJson['dependencies'] as $dependency => $path) {
            if (0 !== \strpos($path, 'file:')) {
                continue;
            }

            $npmPackageFolders[] = $this->projectDir . static::ASSETS_DIR . \substr($path, \strlen('file:'));
        }

        foreach ($npmPackageFolders as $folder) {
            foreach ($filesToCleanup as $blockingFile) {
                $path = $folder . $blockingFile;

                if (!$this->filesystem->exists($path)) {
                    continue;
                }

                $this->filesystem->remove($path);
            }
        }
    }

    private function runProcess(SymfonyStyle $ui, $command): int
    {
        $process = Process::fromShellCommandline($command, $this->projectDir . static::ASSETS_DIR);
        $process->setTimeout(3600);
        $process->run(function($type, $buffer) use ($ui) {
            $ui->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });

        return $process->getExitCode();
    }
}

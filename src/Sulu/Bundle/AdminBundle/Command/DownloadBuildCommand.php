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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DownloadBuildCommand extends Command
{
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

    const ASSETS_DIR = DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;

    const BUILD_DIR = DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'admin';

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
        if (!preg_match(static::VERSION_REGEX, $this->suluVersion)) {
            throw new \Exception(
                'This command only works for tagged sulu versions matching semantic versioning, not for branches etc.'
                . ' Given version was "' . $this->suluVersion . '".'
            );
        }

        $output->writeln('<info>Checking for changed files...</info>');

        $indexJs = static::ASSETS_DIR . 'index.js';
        $packageJson = static::ASSETS_DIR . 'package.json';
        $webpackConfigJs = static::ASSETS_DIR . 'webpack.config.js';

        $localIndexJsHash = $this->getLocaleFileHash($indexJs);
        $localPackageJsonHash = $this->getLocaleFileHash($packageJson);
        $localWebpackConfigJsHash = $this->getLocaleFileHash($webpackConfigJs);

        $remoteIndexJsHash = $this->getRemoteFileHash($indexJs);
        $remotePackageJsonHash = $this->getRemoteFileHash($packageJson);
        $remoteWebpackConfigJsHash = $this->getRemoteFileHash($webpackConfigJs);

        if ($localIndexJsHash !== $remoteIndexJsHash
            || $localPackageJsonHash !== $remotePackageJsonHash
            || $localWebpackConfigJsHash !== $remoteWebpackConfigJsHash
        ) {
            throw new \Exception(
                sprintf(
                    'The files in the local "%s" folder do not match the ones in the remote repository "%s".' . PHP_EOL
                    . 'Either the build has been modified, which means it has to be done manually with NPM or the files'
                    . ' in your repository are outdated and have to be copied from the remote repository.',
                    static::ASSETS_DIR,
                    $this->remoteRepository
                )
            );
        }

        $tempDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . static::REPOSITORY_NAME . uniqid(rand(), true);
        $tempFileZip = $tempDirectory . '.zip';

        $output->writeln('<info>Download remote repository...</info>');
        $response = $this->httpClient->request('GET', $this->remoteArchive);

        $filesystem = new Filesystem();

        file_put_contents($tempFileZip, $response->getContent());

        $zip = new \ZipArchive();
        if ($zip->open($tempFileZip)) {
            $output->writeln('<info>Extract ZIP archive...</info>');
            $zip->extractTo($tempDirectory);
            $zip->close();

            $buildDir = $this->projectDir . static::BUILD_DIR;
            $extractedFolderName = static::REPOSITORY_NAME . '-' . $this->suluVersion;
            $tempProjectDir = $tempDirectory . DIRECTORY_SEPARATOR . $extractedFolderName;

            $output->writeln('<info>Delete old build folder...</info>');
            $filesystem->remove(glob($buildDir . DIRECTORY_SEPARATOR . '*'));

            $output->writeln('<info>Copy build folder from remote repository...</info>');
            $filesystem->mirror(
                $tempProjectDir . static::BUILD_DIR,
                $buildDir
            );

            $filesystem->remove($tempDirectory);
        } else {
            $output->writeln('<error>Error when unpacking the ZIP archive</error>');
        }

        unlink($tempFileZip);
    }

    private function getLocaleFileHash(string $path)
    {
        return $this->hash(file_get_contents($this->projectDir . $path));
    }

    private function getRemoteFileHash(string $path)
    {
        $response = $this->httpClient->request('GET', $this->remoteRepository . $path);

        return $this->hash($response->getContent());
    }

    private function hash($content)
    {
        return hash('sha256', $content);
    }
}

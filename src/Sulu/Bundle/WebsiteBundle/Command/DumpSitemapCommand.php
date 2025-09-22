<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Command;

use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumperInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RequestContext;

#[AsCommand(name: 'sulu:website:dump-sitemap')]
class DumpSitemapCommand extends Command
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var XmlSitemapDumperInterface
     */
    private $sitemapDumper;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $baseDirectory;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        XmlSitemapDumperInterface $sitemapDumper,
        Filesystem $filesystem,
        string $baseDirectory,
        string $environment,
        private string $scheme,
        string $defaultHost,
        ?RequestContext $requestContext = null
    ) {
        parent::__construct();

        $this->webspaceManager = $webspaceManager;
        $this->sitemapDumper = $sitemapDumper;
        $this->filesystem = $filesystem;
        $this->environment = $environment;
        $this->baseDirectory = $baseDirectory;

        if (null === $requestContext) {
            @trigger_deprecation('sulu/sulu', '2.6', 'It is deprecated to not pass the request context to "%s".', self::class);
        } elseif ($requestContext->getScheme() !== $scheme) {
            @trigger_deprecation('sulu/sulu', '2.6', 'It is deprecated to define a different scheme in kernel parameter "router.request_context.scheme" and "framework.router.default_uri" config. Starting with Sulu 3.0 only "framework.router.default_uri" will be respected in "%s".', self::class);
        }
    }

    protected function configure(): void
    {
        $this->addOption('https', null, InputOption::VALUE_NONE, 'Use https scheme for url generation.')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete all file before start.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        if ($input->getOption('https')) {
            $this->scheme = 'https';
        }

        if ($input->getOption('clear')) {
            $this->clear();
        }

        $output->writeln('Start dumping "sitemap.xml" files:');

        $portalInformations = $this->webspaceManager->getPortalInformations($this->environment);

        $hosts = [];
        foreach ($portalInformations as $portalInformation) {
            $portalUrl = $portalInformation->getUrl();
            $urlParts = \parse_url($this->scheme . '://' . $portalUrl);
            $hosts[] = $urlParts['host'];
        }

        $hosts = \array_unique(\array_filter($hosts));

        foreach ($hosts as $host) {
            $this->sitemapDumper->dumpHost($this->scheme, $host);
        }

        return 0;
    }

    /**
     * Clear the sitemap-cache.
     */
    private function clear(): void
    {
        $this->filesystem->remove(\rtrim($this->baseDirectory, '/') . '/' . $this->scheme);
    }
}

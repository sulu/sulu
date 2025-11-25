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
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private XmlSitemapDumperInterface $sitemapDumper,
        private Filesystem $filesystem,
        private string $baseDirectory,
        private string $environment,
        private RequestContext $requestContext,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('clear', null, InputOption::VALUE_NONE, 'Delete all file before start.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        if ($input->getOption('clear')) {
            $this->clear();
        }

        $output->writeln('Start dumping "sitemap.xml" files:');

        $portalInformations = $this->webspaceManager->getPortalInformations($this->environment);

        $hosts = [];
        foreach ($portalInformations as $portalInformation) {
            $portalUrl = $portalInformation->getUrl();
            $urlParts = \parse_url($this->requestContext->getScheme() . '://' . $portalUrl);
            $hosts[] = $urlParts['host'];
        }

        $hosts = \array_unique(\array_filter($hosts));

        foreach ($hosts as $host) {
            $this->sitemapDumper->dumpHost($this->requestContext->getScheme(), $host);
        }

        return 0;
    }

    /**
     * Clear the sitemap-cache.
     */
    private function clear(): void
    {
        $this->filesystem->remove(\rtrim($this->baseDirectory, '/') . '/' . $this->requestContext->getScheme());
    }
}

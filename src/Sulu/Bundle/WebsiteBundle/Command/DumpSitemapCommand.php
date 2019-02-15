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
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Dump sitemaps to filesystem.
 */
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

    /**
     * @var string
     */
    private $scheme = 'http';

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        XmlSitemapDumperInterface $sitemapDumper,
        Filesystem $filesystem,
        string $baseDirectory,
        string $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->sitemapDumper = $sitemapDumper;
        $this->filesystem = $filesystem;
        $this->environment = $environment;
        $this->baseDirectory = $baseDirectory;
        parent::__construct('sulu:website:dump-sitemap');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('https', null, InputOption::VALUE_NONE, 'Use https scheme for url generation.')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete all file before start.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if ($input->getOption('https')) {
            $this->scheme = 'https';
        }

        if ($input->getOption('clear')) {
            $this->clear();
        }

        $output->writeln('Start dumping "sitemap.xml" files:');

        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $this->dumpWebspace($webspace);
        }
    }

    /**
     * Dump given webspace.
     *
     * @param Webspace $webspace
     */
    private function dumpWebspace(Webspace $webspace)
    {
        foreach ($webspace->getAllLocalizations() as $localization) {
            $this->output->writeln(sprintf(' - %s (%s)', $webspace->getKey(), $localization->getLocale()));
            $this->dumpPortalInformations(
                $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
                    $webspace->getKey(),
                    $localization->getLocale(),
                    $this->environment
                )
            );
        }
    }

    /**
     * Dump given portal-informations.
     *
     * @param PortalInformation[] $portalInformations
     */
    private function dumpPortalInformations(array $portalInformations)
    {
        try {
            foreach ($portalInformations as $portalInformation) {
                $this->sitemapDumper->dumpPortalInformation($portalInformation, $this->scheme);
            }
        } catch (\InvalidArgumentException $exception) {
            $this->clear();

            throw $exception;
        }
    }

    /**
     * Clear the sitemap-cache.
     */
    private function clear()
    {
        $this->filesystem->remove(rtrim($this->baseDirectory, '/') . '/' . $this->scheme);
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Command;

use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumperInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Dump sitemaps to filesystem.
 */
class DumpSitemapCommand extends ContainerAwareCommand
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:website:dump-sitemap')
            ->addOption('https', null, InputOption::VALUE_NONE, 'Use https scheme for url generation.')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete all file before start.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->sitemapDumper = $this->getContainer()->get('sulu_website.sitemap.xml_dumper');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $this->filesystem = new Filesystem();

        $this->environment = $this->getContainer()->getParameter('kernel.environment');
        $this->baseDirectory = $this->getContainer()->getParameter('sulu_website.sitemap.dump_dir');

        if ($input->getOption('https')) {
            $this->scheme = 'https';
        }

        if ($input->getOption('clear')) {
            $this->filesystem->remove(rtrim($this->baseDirectory, '/') . '/' . $this->scheme);
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
        foreach ($portalInformations as $portalInformation) {
            $this->sitemapDumper->dumpPortalInformation($portalInformation, $this->scheme);
        }
    }
}

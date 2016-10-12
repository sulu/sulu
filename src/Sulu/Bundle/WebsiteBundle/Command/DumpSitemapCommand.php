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

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapRendererInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
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
     * @var SitemapProviderPoolInterface
     */
    private $sitemapProviderPool;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var XmlSitemapRendererInterface
     */
    private $sitemapRenderer;

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
    private $defaultHost;

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
        $this->sitemapProviderPool = $this->getContainer()->get('sulu_website.sitemap.pool');
        $this->sitemapRenderer = $this->getContainer()->get('sulu_website.sitemap.xml_renderer');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $this->filesystem = new Filesystem();

        $this->environment = $this->getContainer()->getParameter('kernel.environment');
        $this->baseDirectory = $this->getContainer()->getParameter('sulu_website.sitemap.dump_dir');
        $this->defaultHost = $this->getContainer()->getParameter('sulu_website.sitemap.default_host');

        if ($input->getOption('https')) {
            $this->scheme = 'https';
        }

        if ($input->getOption('clear')) {
            $this->filesystem->remove(rtrim($this->baseDirectory, '/') . '/' . $this->scheme);
        }

        $output->writeln('Start dumping "sitemap.xml" files:');

        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            foreach ($webspace->getAllLocalizations() as $localization) {
                $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
                    $webspace->getKey(),
                    $localization->getLocale(),
                    $this->environment
                );
                foreach ($portalInformations as $portalInformation) {
                    $this->dumpSitemap($portalInformation);
                }
            }
        }
    }

    /**
     * Dump sitemaps for portal-information.
     *
     * @param PortalInformation $portalInformation
     */
    private function dumpSitemap(PortalInformation $portalInformation)
    {
        if (false !== strpos($portalInformation->getUrl(), '{host}')) {
            if (!$this->defaultHost) {
                return;
            }

            $portalInformation->setUrl(str_replace('{host}', $this->defaultHost, $portalInformation->getUrl()));
        }

        $filePath = $this->sitemapRenderer->getIndexDumpPath(
            $this->scheme,
            $portalInformation->getWebspaceKey(),
            $portalInformation->getLocale(),
            $portalInformation->getHost()
        );

        $sitemap = $this->sitemapRenderer->renderIndex($portalInformation->getHost(), $this->scheme);
        if (!$sitemap) {
            $aliases = array_keys($this->sitemapProviderPool->getProviders());

            $this->dumpFile(
                $filePath,
                $this->sitemapRenderer->renderSitemap(
                    reset($aliases),
                    1,
                    $portalInformation->getLocale(),
                    $portalInformation->getPortal(),
                    $portalInformation->getHost(),
                    $this->scheme
                )
            );

            return;
        }

        $this->dumpFile($filePath, $sitemap);

        foreach ($this->sitemapProviderPool->getProviders() as $alias => $provider) {
            $this->dumpProviderSitemap($alias, $portalInformation);
        }
    }

    /**
     * Render sitemap for provider.
     *
     * @param string $alias
     * @param PortalInformation $portalInformation
     */
    private function dumpProviderSitemap($alias, PortalInformation $portalInformation)
    {
        $page = 1;
        do {
            $sitemap = $this->sitemapRenderer->renderSitemap(
                $alias,
                $page,
                $portalInformation->getLocale(),
                $portalInformation->getPortal(),
                $portalInformation->getHost(),
                $this->scheme
            );

            $this->dumpFile(
                $this->sitemapRenderer->getDumpPath(
                    $this->scheme,
                    $portalInformation->getWebspaceKey(),
                    $portalInformation->getLocale(),
                    $portalInformation->getHost(),
                    $alias,
                    $page++
                ),
                $sitemap
            );
        } while ($sitemap !== null);
    }

    /**
     * Dump content into given filename.
     *
     * @param string $filePath
     * @param string $content
     */
    private function dumpFile($filePath, $content)
    {
        if (!$content) {
            return;
        }

        $this->output->writeln(sprintf(' - %s://%s', $this->scheme, $this->extractUrl($filePath)));

        $this->filesystem->dumpFile($filePath, $content);
    }

    /**
     * Returns url of given sitemap-dump path.
     *
     * @param $path
     *
     * @return string
     */
    private function extractUrl($path)
    {
        $path = substr($path, strlen($this->baseDirectory) + strlen($this->scheme) + 2);

        preg_match('/([^\/]+)\/([^\/]+)\/(?<url>.+)/', $path, $matches);

        return $matches['url'];
    }
}

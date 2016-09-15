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

        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        foreach ($webspaceManager->getPortalInformations($this->environment) as $portalInformation) {
            $this->dumpSitemap($portalInformation);
        }
    }

    /**
     * Dump sitemaps for portal-information.
     *
     * @param PortalInformation $portalInformation
     */
    private function dumpSitemap(PortalInformation $portalInformation)
    {
        if (-1 !== strpos($portalInformation->getUrl(), '{host}')) {
            if (!$this->defaultHost) {
                return;
            }

            $portalInformation->setUrl(str_replace('{host}', $this->defaultHost, $portalInformation->getUrl()));
        }

        if (!$this->sitemapProviderPool->needsIndex()) {
            return $this->dumpFile(
                '/' . $portalInformation->getUrl() . '/sitemap.xml',
                $this->renderProviderSitemap($this->sitemapProviderPool->getFirstAlias(), $portalInformation)
            );
        }

        $this->dumpFile('/' . $portalInformation->getUrl() . '/sitemap.xml', $this->renderIndex($portalInformation));

        foreach ($this->sitemapProviderPool->getProviders() as $alias => $provider) {
            $this->dumpFile(
                '/' . $portalInformation->getUrl() . '/sitemaps/' . $alias . '.xml',
                $this->renderProviderSitemap($alias, $portalInformation)
            );
        }
    }

    /**
     * Render sitemap for provider.
     *
     * @param string $alias
     * @param PortalInformation $portalInformation
     *
     * @return string
     */
    private function renderProviderSitemap($alias, PortalInformation $portalInformation)
    {
        $provider = $this->sitemapProviderPool->getProvider($alias);
        if (1 >= ($maxPage = (int) $provider->getMaxPage())) {
            return $this->renderSitemap($alias, 1, $portalInformation);
        }

        $pathFormat = '/%s/sitemaps/%s-%s.xml';
        for ($page = 1; $page <= $maxPage; ++$page) {
            $path = sprintf($pathFormat, $portalInformation->getUrl(), $alias, $page);
            $this->dumpFile($path, $this->renderSitemap($alias, $page, $portalInformation));
        }

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap-paginated-index.xml.twig',
            ['alias' => $alias, 'maxPage' => $maxPage]
        );
    }

    /**
     * Render sitemap.
     *
     * @param string $alias
     * @param int $page
     * @param PortalInformation $portalInformation
     *
     * @return string
     */
    private function renderSitemap($alias, $page, PortalInformation $portalInformation)
    {
        $provider = $this->sitemapProviderPool->getProvider($alias);
        $entries = $provider->build($page, $portalInformation->getPortalKey(), $portalInformation->getLocale());

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
            [
                'webspaceKey' => $portalInformation->getWebspaceKey(),
                'locale' => $portalInformation->getLocale(),
                'defaultLocale' => $portalInformation->getPortal()->getXDefaultLocalization()->getLocale(),
                'domain' => $portalInformation->getUrl(),
                'scheme' => $this->scheme,
                'entries' => $entries,
            ]
        );
    }

    /**
     * Render index.
     *
     * @param PortalInformation $portalInformation
     *
     * @return string
     */
    private function renderIndex(PortalInformation $portalInformation)
    {
        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig',
            ['sitemaps' => $this->sitemapProviderPool->getIndex(), 'scheme' => $this->scheme, 'domain' => $portalInformation->getHost()]
        );
    }

    /**
     * Render twig-template with given context.
     *
     * @param $name
     * @param array $context
     *
     * @return string
     */
    private function render($name, array $context)
    {
        return $this->getContainer()->get('twig')->render($name, $context);
    }

    /**
     * Dump content into given filename.
     *
     * @param string $filename
     * @param string $content
     */
    private function dumpFile($filename, $content)
    {
        $this->output->writeln(sprintf(' - %s://%s', $this->scheme, ltrim($filename, '/')));

        $filePath = sprintf('%s/%s/%s', rtrim($this->baseDirectory, '/'), $this->scheme, ltrim($filename, '/'));
        $this->filesystem->dumpFile($filePath, $content);
    }
}

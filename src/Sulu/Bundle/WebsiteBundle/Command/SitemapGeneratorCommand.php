<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Command;

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapDumper;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SitemapGeneratorCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('sulu:website:sitemap:generate')
            ->addArgument('webspace')
            ->addArgument('portal')
            ->setDescription('Generate Sitemap!')
            ->setHelp('The <info>%command.name%</info> command generate the a sitemap for a webspace' . PHP_EOL .
                '%command.full_name% <options> <arguments>' . PHP_EOL .
                'This command should be added to a cronjob if your site is big' . PHP_EOL .
                'and takes time to generate the sitemap for fast response time. ' . PHP_EOL
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = microtime(true);
        $webspaceKey = $input->getArgument('webspace');
        $portalKey = $input->getArgument('portal');
        $style2 = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('done', $style2);

        $output->writeln('Start generating sitemap for: "' . $webspaceKey . '".');
        $output->writeln('Process can take some seconds');

        if (empty($webspaceKey)) {
            $output->writeln('<error>Error: WebspaceKey needed! e.g: "sulu:website:sitemap:generate sulu_io sulu_io"</error>');
            return 1;
        }
        if (empty($portalKey)) {
            $output->writeln('<error>Error: PortalKey needed! e.g: "sulu:website:sitemap:generate sulu_io sulu_io"</error>');
            return 1;
        }

        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        /** @var SitemapGeneratorInterface $sitemapGenerator */
        $sitemapGenerator = $this->getContainer()->get('sulu_website.sitemap');
        /** @var SitemapDumper $sitemapDumper */
        $sitemapDumper = $this->getContainer()->get('sulu_website.sitemap.dumper');

        $defaultLocale = $webspaceManager->findPortalByKey($portalKey)->getDefaultLocalization();
        $sitemapPages = $sitemapGenerator->generateForPortal($webspaceKey, $portalKey, true);
        $sitemapDumper->dump($sitemapPages, $defaultLocale, $webspaceKey, $portalKey, true);

        if ($sitemapDumper->sitemapExists($webspaceKey, $portalKey)) {
            $output->writeln(sprintf('<done>Done: Generated "%s" in %s seconds!</done>', $webspaceKey, (microtime(true) - $time)));
        } else {
            $output->writeln(sprintf('<error>Error: Generating "%s" sitemap!</error>', $webspaceKey));
        }
    }
}

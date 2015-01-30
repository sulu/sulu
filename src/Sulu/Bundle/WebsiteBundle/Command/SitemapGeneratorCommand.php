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

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface;
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
        $style2 = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('done', $style2);

        $output->writeln('Start generating sitemap for: "' . $webspaceKey . '".');
        $output->writeln('Process can take some seconds');

        if (empty($webspaceKey)) {
            $output->writeln('<error>Error: WebspaceKey needed! e.g: "sulu:website:sitemap:generate sulu_io"</error>');
            return 1;
        }

        /** @var SitemapGeneratorInterface $sitemapGenerator */
        $sitemapGenerator = $this->getContainer()->get('sulu_website.sitemap');

        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $localizations = $webspaceManager->findWebspaceByKey($webspaceKey)->getAllLocalizations();

        $localizationCodes = array();
        foreach ($localizations as $localization) {
            $localizationCodes[] = $localization->getLocalization();
        }

        $sitemapPages = $sitemapGenerator->generateAllLocals($webspaceKey, true);
        $output->writeln('Page Count: ' . count($sitemapPages));

        $sitemap = $this->getContainer()->get('templating')->render(
            'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
            array(
                'sitemap' => $sitemapPages,
                'locales' => $localizationCodes,
                'defaultLocale' => $localizations,
                'webspaceKey' => $webspaceKey
            )
        );

        $siteMapFolder = $this->getContainer()->getParameter('sulu_website.sitemap.cache.folder');

        $filesystem = new Filesystem();
        $filesystem->dumpFile(sprintf('%s/%s.xml', $siteMapFolder, $webspaceKey), $sitemap);
        $output->writeln('<done>Done: Generated in '. (microtime(true) - $time) .' seconds!</done>');
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrades Resourcelocators to 0.9.0
 */
class UpgradeInternalLinksCommand extends ContainerAwareCommand
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:upgrade:0.15.0:internal-links')->setDescription('Upgrades internal-links to 0.15.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->session = $this->getContainer()->get('sulu.phpcr.session')->getSession();

        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');

        /** @var Webspace $webspace */
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            $this->upgradeWebspace($webspace, $output);
        }

        $this->session->save();
    }

    private function upgradeWebspace(Webspace $webspace, OutputInterface $output)
    {
        $output->writeln('<info>> Upgrade Webspace: ' . $webspace->getName() . '</info>');
        foreach ($webspace->getAllLocalizations() as $localization) {
            $this->upgradeLocale($webspace, $localization, $output);
        }
    }

    private function upgradeLocale(Webspace $webspace, Localization $localization, OutputInterface $output)
    {
        $output->writeln('  > Upgrade Locale: ' . $localization->getLocalization('-'));

        /** @var ContentMapperInterface $contentMapper */
        $contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $startPage = $contentMapper->loadStartPage($webspace->getKey(), $localization->getLocalization());

        $this->upgradeNode($startPage, $webspace, $localization, $output);
        $this->upgradeByParent($startPage, $webspace, $localization, $contentMapper, $output);
    }

    private function upgradeByParent(
        Structure $parent,
        Webspace $webspace,
        Localization $localization,
        ContentMapperInterface $contentMapper,
        OutputInterface $output
    )
    {
        $pages = $contentMapper->loadByParent(
            $parent->getUuid(),
            $webspace->getKey(),
            $localization->getLocalization(),
            1
        );

        /** @var Page $page */
        foreach ($pages as $page) {
            $this->upgradeNode($page, $webspace, $localization, $output, substr_count($page->getPath(), '/'));
            $this->upgradeByParent($page, $webspace, $localization, $contentMapper, $output);
        }
    }

    private function upgradeNode(
        Structure $page,
        Webspace $webspace,
        Localization $localization,
        OutputInterface $output,
        $depth = 0
    )
    {
        foreach ($page->getProperties(true) as $property) {
            if ($property->getContentTypeName() == 'internal_links') {
                $this->upgradeProperty($page, $localization, $output, $property, $depth);
            }
        }
    }

    private function upgradeProperty(Structure $page, Localization $localization, OutputInterface $output, $property, $depth)
    {
        $node = $this->session->getNodeByIdentifier($page->getUuid());

        $transProperty = new TranslatedProperty(
            $property,
            $localization->getLocalization(),
            $this->getContainer()->getParameter('sulu.content.language.namespace')
        );

        if ($node->hasProperty($transProperty->getName())) {
            $value = json_decode($node->getPropertyValueWithDefault($transProperty->getName(), '{ids: []}'), true);
            $node->getProperty($transProperty->getName())->remove();
            $node->setProperty($transProperty->getName(), $value['ids'], PropertyType::REFERENCE);

            $prefix = '   ';
            for ($i = 0; $i < $depth; $i++) {
                $prefix .= '-';
            }

            $output->writeln($prefix . '> ' . $page->getPropertyValue('title'));
        }
    }
}

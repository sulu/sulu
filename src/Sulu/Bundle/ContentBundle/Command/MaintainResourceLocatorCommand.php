<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrades Resourcelocators to 0.9.0.
 *
 * @deprecated
 */
class MaintainResourceLocatorCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:content:resource-locator:maintain')
            ->setDescription('Resets the cached url value on every node');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');

        /** @var Webspace $webspace */
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            $this->upgradeWebspace($webspace, $output);
        }
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
        StructureBridge $parent,
        Webspace $webspace,
        Localization $localization,
        ContentMapperInterface $contentMapper,
        OutputInterface $output
    ) {
        $pages = $contentMapper->loadByParent(
            $parent->getUuid(),
            $webspace->getKey(),
            $localization->getLocalization(),
            1
        );

        foreach ($pages as $page) {
            $this->upgradeNode($page, $webspace, $localization, $output, substr_count($page->getPath(), '/'));
            $this->upgradeByParent($page, $webspace, $localization, $contentMapper, $output);
        }
    }

    private function upgradeNode(
        StructureBridge $page,
        Webspace $webspace,
        Localization $localization,
        OutputInterface $output,
        $depth = 0
    ) {
        /** @var SessionManagerInterface $sessionManager */
        $sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $session = $sessionManager->getSession();
        $node = $session->getNodeByIdentifier($page->getUuid());

        /** @var RlpStrategyInterface $strategy */
        $strategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');

        /** @var ResourceLocator $resourceLocator */
        $resourceLocator = $this->getContainer()->get('sulu.content.type.resource_locator');

        if (!$page->hasTag('sulu.rlp')) {
            return;
        }

        $property = $page->getPropertyByTagName('sulu.rlp');
        if (
            $property->getContentTypeName() !== 'resource_locator' &&
            $page->getNodeType() !== Structure::NODE_TYPE_CONTENT
        ) {
            return;
        }

        $transProperty = new TranslatedProperty(
            $property,
            $localization->getLocalization(),
            $this->getContainer()->getParameter('sulu.content.language.namespace')
        );

        try {
            // load value
            $rl = $strategy->loadByContent($node, $webspace->getKey(), $localization->getLocalization());

            // save value
            $property->setValue($rl);
            $resourceLocator->write(
                $node,
                $transProperty,
                1,
                $webspace->getKey(),
                $localization->getLocalization(),
                null
            );
            $session->save();

            $prefix = '   ';
            for ($i = 0; $i < $depth; ++$i) {
                $prefix .= '-';
            }

            $output->writeln($prefix . '> "' . $page->getPropertyValue('title') . '": ' . $rl);
        } catch (ResourceLocatorNotFoundException $ex) {
        }
    }
}

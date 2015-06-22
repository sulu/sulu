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

use PHPCR\SessionInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Component\Content\Block\BlockPropertyInterface;
use Sulu\Component\Content\Block\BlockPropertyWrapper;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrades time to 0.16.0.
 *
 * @deprecated
 */
class UpgradeTimeCommand extends ContainerAwareCommand
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:upgrade:0.16.0:time')->setDescription('Upgrades time to 0.16.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->session = $this->getContainer()->get('sulu.phpcr.session')->getSession();

        /** @var WebspaceManagerInterface $webspaceManager */
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $this->upgradeWebspace($webspace, $output);
        }

        if ($this->getContainer()->has('sulu_snippet.repository')) {
            $output->writeln('<info> Upgrade Snippets: </info>');
            foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
                $this->upgradeSnippets($output, $localization);
            }
        }

        $this->session->save();
    }

    private function upgradeWebspace(Webspace $webspace, OutputInterface $output)
    {
        $output->writeln('<info> Upgrade Webspace: ' . $webspace->getName() . '</info>');
        foreach ($webspace->getAllLocalizations() as $localization) {
            $output->writeln('  > Upgrade Locale: ' . $localization->getLocalization('-'));

            /** @var ContentMapperInterface $contentMapper */
            $contentMapper = $this->getContainer()->get('sulu.content.mapper');
            $startPage = $contentMapper->loadStartPage($webspace->getKey(), $localization->getLocalization());

            $this->upgradeNode($startPage, $localization, $output);
            $this->upgradeByParent($startPage, $webspace, $localization, $contentMapper, $output);
        }
    }

    private function upgradeSnippets(OutputInterface $output, Localization $localization)
    {
        /**
         * @var SnippetRepository
         */
        $snippetRepository = $this->getContainer()->get('sulu_snippet.repository');

        foreach ($snippetRepository->getSnippets($localization) as $snippet) {
            $this->upgradeNode($snippet, $localization, $output);
        }
    }

    private function upgradeByParent(
        Structure $parent,
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

        /** @var Page $page */
        foreach ($pages as $page) {
            $this->upgradeNode($page, $localization, $output, substr_count($page->getPath(), '/'));
            $this->upgradeByParent($page, $webspace, $localization, $contentMapper, $output);
        }
    }

    private function upgradeNode(
        Structure $structure,
        Localization $localization,
        OutputInterface $output,
        $depth = 0
    ) {
        foreach ($structure->getProperties(true) as $property) {
            if ($property->getContentTypeName() == 'time') {
                $transProperty = new TranslatedProperty(
                    $property,
                    $localization->getLocalization(),
                    $this->getContainer()->getParameter('sulu.content.language.namespace')
                );
                $this->upgradeProperty($structure, $output, $transProperty, $depth);
            } else {
                if ($property instanceof BlockPropertyInterface) {
                    $this->upgradeBlockProperty($structure, $localization, $output, $property, $depth);
                }
            }
        }
    }

    private function upgradeBlockProperty(
        Structure $structure,
        Localization $localization,
        OutputInterface $output,
        BlockPropertyInterface $property,
        $depth
    ) {
        for ($i = 0; $i < $property->getLength(); $i++) {
            $blockProperty = $property->getProperties($i);
            foreach ($blockProperty->getChildProperties() as $childProperty) {
                if ($childProperty->getContentTypeName() == 'time') {
                    $transProperty = new TranslatedProperty(
                        new BlockPropertyWrapper(
                            $childProperty,
                            $property,
                            $i
                        ),
                        $localization->getLocalization(),
                        $this->getContainer()->getParameter('sulu.content.language.namespace')
                    );
                    $this->upgradeProperty($structure, $output, $transProperty, $depth);
                }
            }
        }
    }

    private function upgradeProperty(
        Structure $structure,
        OutputInterface $output,
        $property,
        $depth
    ) {
        $node = $this->session->getNodeByIdentifier($structure->getUuid());

        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValueWithDefault($property->getName(), null);

            if ($value === null) {
                return;
            }

            $dateTime = false;
            $formats = array(
                'H:i',
                'H:i:s',
                'h:i a',
                'h:ia',
                'h:i:s a',
                'h:i:sa',
            );

            foreach ($formats as $format) {
                $dateTime = \DateTime::createFromFormat($format, $value);

                if ($dateTime !== false) {
                    break;
                }
            }

            if ($dateTime !== false) {
                $value = $dateTime->format('H:i:s');
                $node->setProperty($property->getName(), $value);
            } else {
                $value = null;
                $node->getProperty($property->getName())->remove();
            }

            $prefix = '   ';
            for ($i = 0; $i < $depth; $i++) {
                $prefix .= '-';
            }

            $output->writeln($prefix . '> ' . $structure->getPropertyValue('title') . ': ' . print_r($value, true));
        }
    }
}

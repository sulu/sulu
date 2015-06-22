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
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Structure\Snippet;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrades snippet state to RC3.
 *
 * @deprecated
 */
class UpgradeSnippetStateCommand extends ContainerAwareCommand
{
    /**
     * @var SnippetRepository
     */
    private $snippetRepository;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:upgrade:rc3:snippet-state')->setDescription('Upgrades snippet state to RC3.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->session = $this->getContainer()->get('sulu.phpcr.session')->getSession();
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $this->snippetRepository = $this->getContainer()->get('sulu_snippet.repository');
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');

        if ($this->getContainer()->has('sulu_snippet.repository')) {
            $output->writeln('<info> Upgrade Snippets: </info>');
            foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
                $this->upgradeSnippets($output, $localization);
            }
        }

        $this->session->save();
    }

    private function upgradeSnippets(OutputInterface $output, Localization $localization)
    {
        foreach ($this->snippetRepository->getSnippets($localization) as $snippet) {
            $this->upgradeSnippet($snippet, $localization, $output);
        }
    }

    private function upgradeSnippet(
        Snippet $snippet,
        Localization $localization,
        OutputInterface $output
    ) {
        $mapperRequest = ContentMapperRequest::create()
        ->setType('snippet')
        ->setTemplateKey($snippet->getKey())
        ->setUuid($snippet->getUuid())
        ->setLocale($localization->getLocalization())
        ->setUserId($snippet->getChanger())
        ->setData($snippet->toArray(true))
        ->setState(StructureInterface::STATE_PUBLISHED);

        $snippet = $this->contentMapper->saveRequest($mapperRequest);

        $output->writeln(
            sprintf(
                '  > %s: %s - %s - %s',
                $snippet->getKey(),
                $snippet->getPropertyValue('title'),
                $snippet->getLanguageCode(),
                $snippet->getNodeState()
            )
        );
    }
}

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use DateTime;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates default routes in PHPCR for webspaces
 */
class WebspacesInitCommand extends ContainerAwareCommand
{
    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    protected function configure()
    {
        $this->setName('sulu:webspaces:init')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, '', 1)
            ->setDescription('Creates default nodes in PHPCR for webspaces');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('sulu_document_manager.initializer')->initialize($output);
    }
}

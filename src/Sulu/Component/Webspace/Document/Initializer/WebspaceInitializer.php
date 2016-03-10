<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Document\Initializer;

use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Output\OutputInterface;

class WebspaceInitializer implements InitializerInterface
{
    private $webspaceManager;
    private $documentManager;
    private $pathBuilder;
    private $inspector;
    private $nodeManager;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        DocumentManager $documentManager,
        DocumentInspector $inspector,
        PathBuilder $pathBuilder,
        NodeManager $nodeManager
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->documentManager = $documentManager;
        $this->pathBuilder = $pathBuilder;
        $this->inspector = $inspector;
        $this->nodeManager = $nodeManager;
    }

    public function initialize(OutputInterface $output)
    {
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $this->initializeWebspace($output, $webspace);
        }

        $this->documentManager->flush();
    }

    private function initializeWebspace(OutputInterface $output, Webspace $webspace)
    {
        $homePath = $this->pathBuilder->build(['%base%', $webspace->getKey(), '%content%']);
        $routesPath = $this->pathBuilder->build(['%base%', $webspace->getKey(), '%route%']);

        $webspaceLocales = [];
        foreach ($webspace->getAllLocalizations() as $localization) {
            $webspaceLocales[] = $localization->getLocalization();
        }

        if ($this->nodeManager->has($homePath)) {
            $homeDocument = $this->documentManager->find($homePath, 'fr', [
                'load_ghost_content' => false,
                'auto_create' => true,
                'path' => $homePath,
            ]);
            $existingLocales = $this->inspector->getLocales($homeDocument);
        } else {
            $homeDocument = new HomeDocument();
            $homeDocument->setTitle('Homepage');
            $homeDocument->setStructureType($webspace->getTheme()->getDefaultTemplate('homepage'));
            $homeDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
            $existingLocales = [];
        }

        foreach ($webspaceLocales as $webspaceLocale) {
            if (in_array($webspaceLocale, $existingLocales)) {
                $output->writeln(sprintf('  [ ] <info>homepage</info>: %s (%s)', $homePath, $webspaceLocale));
                continue;
            }

            $output->writeln(sprintf('  [+] <info>homepage</info>: %s (%s)', $homePath, $webspaceLocale));
            $this->nodeManager->createPath($routesPath . '/' . $webspaceLocale);
            $this->documentManager->persist($homeDocument, $webspaceLocale, [
                'path' => $homePath,
            ]);
        }

        $this->documentManager->flush();
    }
}

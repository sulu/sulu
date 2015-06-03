<?php

namespace Sulu\Component\Webspace\Document\Initializer;

use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\Webspace\Webspace;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
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
        DocumentManagerInterface $documentManager,
        DocumentInspector $inspector,
        PathBuilder $pathBuilder,
        NodeManager $nodeManager
    )
    {
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
        $homePath = $this->pathBuilder->build(array('%base%', $webspace->getKey(), '%content%'));
        $routesPath = $this->pathBuilder->build(array('%base%', $webspace->getKey(), '%route%'));

        $webspaceLocales = array();
        foreach ($webspace->getAllLocalizations() as $localization) {
            $webspaceLocales[] = $localization->getLocalization();
        }

        if ($this->nodeManager->has($homePath)) {
            $homeDocument = $this->documentManager->find($homePath, 'fr', array(
                'load_ghost_content' => false,
                'auto_create' => true,
                'path' => $homePath
            ));
            $existingLocales = $this->inspector->getLocales($homeDocument);
        } else {
            $homeDocument = new HomeDocument();
            $homeDocument->setTitle('Homepage');
            $homeDocument->setStructureType('overview');
            $homeDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
            $existingLocales = array();
        }

        foreach ($webspaceLocales as $webspaceLocale) {
            $output->writeln(sprintf('<info>Homepage</info>: %s (%s)', $homePath, $webspaceLocale));
            if (in_array($webspaceLocale, $existingLocales)) {
                continue;
            }

            $this->nodeManager->createPath($routesPath . '/' . $webspaceLocale);
            $this->documentManager->persist($homeDocument, $webspaceLocale, array(
                'path' => $homePath
            ));
        }
    }
}

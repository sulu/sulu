<?php

namespace Sulu\Component\Webspace\DocumentManager;

use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\Webspace\Webspace;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Symfony\Component\Console\Output\OutputInterface;
 
class WebspaceInitializer implements InitializerInterface
{
    private $webspaceManager;
    private $documentManager;
    private $pathSegmentRegistry;
    private $inspector;
    private $nodeManager;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        DocumentManager $documentManager,
        DocumentInspector $inspector,
        PathSegmentRegistry $pathSegmentRegistry,
        NodeManager $nodeManager
    )
    {
        $this->webspaceManager = $webspaceManager;
        $this->documentManager = $documentManager;
        $this->pathSegmentRegistry = $pathSegmentRegistry;
        $this->inspector = $inspector;
        $this->nodeManager = $nodeManager;
    }

    public function initialize(OutputInterface $output)
    {
        $this->start = microtime(true);
        $this->initializeBase();

        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $output->writeln(sprintf('Initializing webspace "%s"', $webspace->getKey()));
            $this->initializeWebspace($webspace);
        }

        $this->documentManager->flush();
    }

    private function initializeBase()
    {
        $basePath = '/' . $this->pathSegmentRegistry->getPathSegment('base');

        if (!$this->nodeManager->has($basePath)) {
            $this->nodeManager->createPath($basePath);
        }
    }

    private function initializeWebspace(Webspace $webspace)
    {
        $webspacePath = '/' . $this->pathSegmentRegistry->getPathSegment('base') . '/' . $webspace->getKey();

        $webspaceLocales = array();

        foreach ($webspace->getAllLocalizations() as $localization) {
            $webspaceLocales[] = $localization->getLocalization();
        }

        if (!$this->nodeManager->has($webspacePath)) { 
            $this->nodeManager->createPath($webspacePath);
        }

        $webspaceDocument = $this->documentManager->find($webspacePath);

        $routesPath = $webspacePath . '/' . $this->pathSegmentRegistry->getPathSegment('route');

        if (!$this->nodeManager->has($routesPath)) {
            $this->nodeManager->createPath($routesPath);
        }

        $homePath = $webspacePath . '/' . $this->pathSegmentRegistry->getPathSegment('content');

        if ($this->nodeManager->has($homePath)) {
            $homeDocument = $this->documentManager->find($homePath, null);
            $existingLocales = $this->inspector->getLocales($homeDocument);
        } else {
            $homeNode = $this->nodeManager->createPath($homePath);
            $homeNode->addMixin('sulu:home');

            $homeDocument = $this->documentManager->find($homePath, null, array('type' => 'home'));
            $homeDocument->setTitle('Homepage');
            $homeDocument->setStructureType('overview');
            $homeDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
            $homeDocument->setParent($webspaceDocument);
            $existingLocales = array();
        }

        foreach ($webspaceLocales as $webspaceLocale) {
            if (in_array($webspaceLocale, $existingLocales)) {
                continue;
            }

            $this->nodeManager->createPath($routesPath . '/' . $webspaceLocale);
            $this->documentManager->persist($homeDocument, $webspaceLocale);
        }
    }
}

<?php

namespace DTL\Bundle\ContentBundle\Initializer;

use Doctrine\Bundle\PHPCRBundle\Initializer\InitializerInterface;
use Doctrine\Bundle\PHPCRBundle\ManagerRegistry;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use PHPCR\Util\NodeHelper;

class PhpcrOdmInitializer implements InitializerInterface
{
    private $paths;
    private $webspaceManager;

    public function __construct(WebspaceManager $webspaceManager, $paths = array())
    {
        $this->webspaceManager = $webspaceManager;
        $this->paths = array_merge(array(
            'base' => '/cmf',
            'content' => 'contents',
            'route' => 'routes',
            'snippet' => 'snippets',
        ), $paths);
    }

    public function getName()
    {
        return 'Sulu initializer';
    }

    public function init(ManagerRegistry $registry)
    {
        $documentManager = $registry->getManager();
        $session = $documentManager->getPhpcrSession();
        $baseNode = NodeHelper::createPath($session, $this->paths['base']);

        $webspaceCollection = $this->webspaceManager->getWebspaceCollection();

        foreach ($webspaceCollection as $webspace) {
            $webspaceKey = $webspace->getKey();

            if ($baseNode->hasNode($webspaceKey)) {
                $webspaceNode = $baseNode->getNode($webspaceKey);
            } else {
                $webspaceNode = $baseNode->addNode($webspaceKey, 'sulu:webspace');
            }

            foreach (array('snippet', 'content', 'route') as $folderName) {
                $folderName = $this->paths[$folderName];
                if (!$webspaceNode->hasNode($folderName)) {
                    $webspaceNode->addNode($folderName);
                }
            }
        }

        $session->save();
    }
}

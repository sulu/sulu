<?php

namespace Sulu\Bundle\CoreBundle\Initializer;

use Doctrine\Bundle\PHPCRBundle\Initializer\InitializerInterface;
use Doctrine\Bundle\PHPCRBundle\ManagerRegistry;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use PHPCR\Util\NodeHelper;

class SuluInitializer implements InitializerInterface
{
    protected $basePath;
    protected $webspaceManager;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        $baseNodeName,
        $contentNodeName,
        $routeNodeName,
        $tempNodeName
    )
    {
        $this->nodeNames = array(
            'base' => $baseNodeName,
            'content' => $contentNodeName,
            'route' => $routeNodeName,
            'temp' => $tempNodeName
        );
        $this->webspaceManager = $webspaceManager;
    }

    public function getName()
    {
        return 'Sulu Webspace Initializer';
    }

    public function init(ManagerRegistry $registry)
    {
        $session = $registry->getManager()->getPhpcrSession();
        $webspaceCollection = $this->webspaceManager->getWebspaceCollection();

        foreach ($webspaceCollection as $webspace) {
            foreach (array('content', 'route', 'temp') as $nodeName) {
                NodeHelper::createPath($session, sprintf(
                    '%s/%s/%s',
                    $this->nodeNames['base'],
                    $webspace->getKey(),
                    $nodeName
                ));
            }
        }

        $session->save();
    }
}

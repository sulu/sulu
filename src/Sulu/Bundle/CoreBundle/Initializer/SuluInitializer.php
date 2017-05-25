<?php

namespace Sulu\Bundle\CoreBundle\Initializer;

use Doctrine\Bundle\PHPCRBundle\Initializer\InitializerInterface;
use Doctrine\Bundle\PHPCRBundle\ManagerRegistry;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use PHPCR\Util\NodeHelper;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Mapper\ContentMapper;
use PHPCR\Util\PathHelper;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Component\Webspace\Localization;

class SuluInitializer implements InitializerInterface
{
    private $nodeNames;
    private $webspaceManager;
    private $contentMapper;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        ContentMapper $contentMapper,
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
        $this->contentMapper = $contentMapper;
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
            foreach (array('contents', 'route', 'temp') as $nodeName) {
                $path = sprintf(
                    '%s/%s/%s',
                    $this->nodeNames['base'],
                    $webspace->getKey(),
                    $nodeName
                );

                switch ($nodeName) {
                    case 'contents': 
                        $node = NodeHelper::createPath($session, $path);
                        $node->addMixin('mix:referenceable');
                        $session->save();

                        foreach ($webspace->getLocalizations() as $localization) {
                            $this->createHomepage($webspace, $localization, $node);
                        }
                        $session->save();
                        break;
                    case 'route':
                        // $this->createBaseRoutes($node);
                        break;
                }
            }
        }
    }

    public function createHomepage(Webspace $webspace, Localization $localization, $node)
    {
        $request = ContentMapperRequest::create()
            ->setType(Structure::TYPE_PAGE)
            ->setData(array(
                'title' => ''
            ))
            ->setTemplateKey('overview')
            ->setUserId(1)
            ->setLocale($localization->getLocalization())
            ->setUuid($node->getIdentifier())
            ->setWebspaceKey($webspace->getKey())
            ->setState(StructureInterface::STATE_PUBLISHED);

        $this->contentMapper->saveRequest($request);

        $subLocalizations = $localization->getChildren() ? : array();

        foreach ($subLocalizations as $subLocalization) {
            $this->createHomepage($webspace, $subLocalization, $node);
        }
    }
}

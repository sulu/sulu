<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContactBundle\Controller\ContactsController;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\RestController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ResourcelocatorController extends Controller implements ClassResourceInterface
{
    /**
     * return resource-locator for sub-node
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction()
    {
        $parent = $this->getRequest()->get('parent');
        $title = $this->getRequest()->get('title');
        $portal = $this->getRequest()->get('portal');
        if ($title == null) {
            throw new MissingArgumentException('ResourceLocator', 'title');
        }
        if ($portal == null) {
            throw new MissingArgumentException('ResourceLocator', 'portal');
        }

        $result = array(
            'resourceLocator' => $this->getResourceLocator($title, $parent, $portal)
        );

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function getResourceLocator($title, $parentUuid, $portal)
    {
        $strategy = $this->getStrategy($portal);
        if ($parentUuid !== null) {
            $parentPath = $strategy->loadByContentUuid($parentUuid, $portal);
        } else {
            $parentPath = '/';
        }

        return $strategy->generate($title, $parentPath, $portal);
    }

    /**
     * @param $portal
     * @return RlpStrategyInterface
     */
    private function getStrategy($portal)
    {
        // FIXME get strategy key for portal ($portal)
        $strategy = 'tree';

        return $this->get('sulu.content.rlp.strategy.' . $strategy);
    }

    /**
     * @return ResourceLocatorInterface
     */
    private function getResourceLocatorType()
    {
        return $this->get('sulu.content.type.resource_locator');
    }
}

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
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ResourceLocatorController extends Controller implements ClassResourceInterface
{
    /**
     * return resource-locator for sub-node
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction()
    {
        $parent = $this->getRequest()->get('parent');
        $uuid = $this->getRequest()->get('uuid');
        $title = $this->getRequest()->get('title');
        $webspace = $this->getRequest()->get('webspace');
        $languageCode = $this->getRequest()->get('language');
        if ($title == null) {
            throw new MissingArgumentException('ResourceLocator', 'title');
        }
        if ($webspace == null) {
            throw new MissingArgumentException('ResourceLocator', 'webspace');
        }

        $result = array(
            'resourceLocator' => $this->getResourceLocator($title, $parent, $uuid, $webspace, $languageCode, null)
        );

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function getResourceLocator($title, $parentUuid, $uuid, $webspaceKey, $languageCode, $segmentKey)
    {
        $strategy = $this->getStrategy($webspaceKey);
        if ($parentUuid !== null) {
            $parentPath = $strategy->loadByContentUuid($parentUuid, $webspaceKey, $languageCode, $segmentKey);
            return $strategy->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
        } elseif ($uuid !== null) {
            return $strategy->generateForUuid($title, $uuid, $webspaceKey, $languageCode, $segmentKey);
        } else {
            $parentPath = '/';
            return $strategy->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    /**
     * @param $webspaceKey
     * @return RlpStrategyInterface
     */
    private function getStrategy($webspaceKey)
    {
        // FIXME get strategy key for portal ($portal)
        $strategy = 'tree';

        return $this->get('sulu.content.rlp.strategy.' . $strategy);
    }
}

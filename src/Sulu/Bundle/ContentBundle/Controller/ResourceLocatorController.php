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
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles resource locator api
 */
class ResourceLocatorController extends Controller
{
    /**
     * return resource-locator for sub-node
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $parent = $this->getRequest()->get('parent');
        $uuid = $this->getRequest()->get('uuid');
        $parts = $this->getRequest()->get('parts');
        $webspace = $this->getRequest()->get('webspace');
        $languageCode = $this->getRequest()->get('language');
        $templateKey = $this->getRequest()->get('template');
        if ($languageCode == null) {
            throw new MissingArgumentException('ResourceLocator', 'languageCode');
        }
        if ($parts == null) {
            throw new MissingArgumentException('ResourceLocator', 'title');
        }
        if ($webspace == null) {
            throw new MissingArgumentException('ResourceLocator', 'webspace');
        }

        /** @var StructureInterface $structure */
        $structure = $this->get('sulu.content.structure_manager')->getStructure($templateKey);
        $title = '';
        // concat rlp parts in sort of priority
        foreach ($structure->getPropertiesByTagName('sulu.rlp.part') as $property) {
            $title = $parts[$property->getName()] . '-' . $title;
        }
        $title = substr($title, 0, -1);

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

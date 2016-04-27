<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DataCollector;

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class SuluCollector extends DataCollector
{
    public function data($key)
    {
        return $this->data[$key];
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if (!$request->attributes->has('_sulu')) {
            return;
        }

        /** @var RequestAttributes $requestAttributes */
        $requestAttributes = $request->attributes->get('_sulu');

        $webspace = $requestAttributes->getAttribute('webspace');
        $portal = $requestAttributes->getAttribute('portal');
        $segment = $requestAttributes->getAttribute('segment');

        $this->data['match_type'] = $requestAttributes->getAttribute('matchType');
        $this->data['redirect'] = $requestAttributes->getAttribute('redirect');
        $this->data['portal_url'] = $requestAttributes->getAttribute('portalUrl');

        if ($webspace) {
            $this->data['webspace'] = $webspace->toArray();
        }

        if ($portal) {
            $this->data['portal'] = $portal->toArray();
        }

        if ($segment) {
            $this->data['segment'] = $segment->toArray();
        }

        $this->data['localization'] = $requestAttributes->getAttribute('localization');
        $this->data['resource_locator'] = $requestAttributes->getAttribute('resourceLocator');
        $this->data['resource_locator_prefix'] = $requestAttributes->getAttribute('resourceLocatorPrefix');

        $structure = null;
        if ($request->attributes->has('_route_params')) {
            $params = $request->attributes->get('_route_params');
            if (isset($params['structure'])) {
                /** @var StructureInterface $structureObject */
                $structureObject = $params['structure'];

                $structure = [
                    'id' => $structureObject->getUuid(),
                    'path' => $structureObject->getPath(),
                    'nodeType' => $structureObject->getNodeType(),
                    'internal' => $structureObject->getInternal(),
                    'nodeState' => $structureObject->getNodeState(),
                    'published' => $structureObject->getPublished(),
                    'publishedState' => $structureObject->getPublishedState(),
                    'navContexts' => $structureObject->getNavContexts(),
                    'enabledShadowLanguages' => $structureObject->getEnabledShadowLanguages(),
                    'concreteLanguages' => $structureObject->getConcreteLanguages(),
                    'shadowOn' => $structureObject->getIsShadow(),
                    'shadowBaseLanguage' => $structureObject->getShadowBaseLanguage(),
                    'template' => $structureObject->getKey(),
                    'originTemplate' => $structureObject->getOriginTemplate(),
                    'hasSub' => $structureObject->getHasChildren(),
                    'creator' => $structureObject->getCreator(),
                    'changer' => $structureObject->getChanger(),
                    'created' => $structureObject->getCreated(),
                    'changed' => $structureObject->getChanged(),
                ];
            }
        }
        $this->data['structure'] = $structure;
    }

    public function getName()
    {
        return 'sulu';
    }
}

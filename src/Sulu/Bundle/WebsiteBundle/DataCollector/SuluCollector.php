<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class SuluCollector extends DataCollector
{
    protected $requestAnalyzer;

    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function data($key)
    {
        return $this->data[$key];
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $requestAnalyzer = $this->requestAnalyzer;

        $webspace = $requestAnalyzer->getCurrentWebspace();
        $portal = $requestAnalyzer->getCurrentPortal();
        $segment = $requestAnalyzer->getCurrentSegment();

        $this->data['match_type'] = $requestAnalyzer->getCurrentMatchType();
        $this->data['redirect'] = $requestAnalyzer->getCurrentRedirect();
        $this->data['portal_url'] = $requestAnalyzer->getCurrentPortalUrl();

        if ($webspace) {
            $this->data['webspace'] = $webspace->toArray();
        }

        if ($portal) {
            $this->data['portal'] = $portal->toArray();
        }

        if ($segment) {
            $this->data['segment'] = $segment->toArray();
        }

        $this->data['localization'] = $requestAnalyzer->getCurrentLocalization();
        $this->data['resource_locator'] = $requestAnalyzer->getCurrentResourceLocator();
        $this->data['resource_locator_prefix'] = $requestAnalyzer->getCurrentResourceLocatorPrefix();

        $structure = null;
        if ($request->attributes->has('_route_params')) {
            $params = $request->attributes->get('_route_params');
            if (isset($params['structure'])) {
                $structure = $params['structure']->toArray();
            }
        }
        $this->data['structure'] = $structure;
    }

    public function getName()
    {
        return 'sulu';
    }
}


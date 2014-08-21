<?php

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
        $ra = $this->requestAnalyzer;

        $this->data['match_type'] = $ra->getCurrentMatchType();
        $this->data['redirect'] = $ra->getCurrentRedirect();
        $this->data['portal_url'] = $ra->getCurrentPortalUrl();

        $this->data['webspace'] = $ra->getCurrentWebspace()->toArray();
        $this->data['portal'] = $ra->getCurrentPortal()->toArray();
        $this->data['segment'] = $ra->getCurrentSegment() ? $ra->getCurrentSegment()->toArray() : null;
        $this->data['localization'] = $ra->getCurrentLocalization();
        $this->data['resource_locator'] = $ra->getCurrentResourceLocator();
        $this->data['resource_locator_prefix'] = $ra->getCurrentResourceLocatorPrefix();

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


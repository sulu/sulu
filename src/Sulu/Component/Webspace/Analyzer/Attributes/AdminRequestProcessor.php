<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts attributes from request for the sulu-admin.
 */
class AdminRequestProcessor implements RequestProcessorInterface
{
    /**
     * @param string $environment
     */
    public function __construct(private WebspaceManagerInterface $webspaceManager, private $environment)
    {
    }

    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $attributes = [];
        $attributes['webspaceKey'] = $this->getWebspaceKey($request);
        $attributes['locale'] = $this->getLocale($request);

        $request->attributes->set(RequestAttributeEnum::WEBSPACE->value, $attributes['webspaceKey']); // TODO move in own request listener

        if ($attributes['locale']) {
            $attributes['localization'] = Localization::createFromString($attributes['locale']);
        }

        if (empty($attributes['webspaceKey'])) {
            return new RequestAttributes($attributes);
        }

        $attributes['webspace'] = $this->webspaceManager->findWebspaceByKey($attributes['webspaceKey']);

        if (null === $attributes['locale']) {
            return new RequestAttributes($attributes);
        }

        $attributes['localization'] = $attributes['webspace']->getLocalization($attributes['locale']);

        return new RequestAttributes($attributes);
    }

    public function validate(RequestAttributes $attributes)
    {
        return true;
    }

    /**
     * The webspace is either part of the route (e.g. "/admin/api/webspaces/{webspace}/analytics") or is sent
     * as query parameter by the administration interface (e.g. "/admin/api/pages?webspace=example").
     *
     * Some routes and requests of the administration interface use "webspaceKey" as parameter name instead
     * (e.g. "/admin/api/webspaces/{webspaceKey}" or "/admin/preview/render?webspaceKey=example"), therefore both
     * parameter names are read from the route and from the query.
     */
    private function getWebspaceKey(Request $request): ?string
    {
        return $request->attributes->getString('webspace')
            ?: $request->query->getString('webspace')
            ?: $request->attributes->getString('webspaceKey')
            ?: $request->query->getString('webspaceKey')
            ?: null;
    }

    /**
     * The locale is sent as query parameter by the administration interface, the "language" parameter is only
     * kept as fallback for consumers which still use the parameter name of the sulu 1.x administration interface.
     */
    private function getLocale(Request $request): ?string
    {
        return $request->query->getString('locale')
            ?: $request->query->getString('language')
            ?: null;
    }
}

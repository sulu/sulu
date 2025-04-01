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
        $attributes['webspaceKey'] = $request->get('webspace');
        $attributes['locale'] = $request->get('locale', $request->get('language'));

        $request->attributes->set(RequestAttributeEnum::SITE->value, $attributes['webspaceKey']); // TODO move in own request listener

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
}

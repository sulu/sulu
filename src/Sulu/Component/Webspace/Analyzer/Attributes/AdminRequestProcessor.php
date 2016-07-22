<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts attributes from request for the sulu-admin.
 */
class AdminRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $attributes = [];
        $attributes['webspaceKey'] = $request->get('webspace');
        $attributes['locale'] = $request->get('locale', $request->get('language'));

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

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        return true;
    }
}

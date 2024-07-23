<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Application\Controller;

use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController as SymfonyTemplateController;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends SymfonyTemplateController
{
    public function templateAction(string $template, ?int $maxAge = null, ?int $sharedAge = null, ?bool $private = null, array $context = [], int $statusCode = 200, ?int $reverseProxyTtl = null): Response
    {
        $response = parent::templateAction($template, $maxAge, $sharedAge, $private, $context, $statusCode);

        if (null !== $reverseProxyTtl) {
            $response->headers->set(SuluHttpCache::HEADER_REVERSE_PROXY_TTL, $reverseProxyTtl);
        }

        return $response;
    }
}

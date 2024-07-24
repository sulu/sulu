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
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * @internal
 */
class TemplateController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function templateAction(string $template, ?int $maxAge = null, ?int $sharedAge = null, ?bool $private = null, array $context = [], int $statusCode = 200, ?int $reverseProxyTtl = null): Response
    {
        $content = $this->twig->render($template, $context);
        $response = new Response($content, $statusCode);

        if ($maxAge) {
            $response->setMaxAge($maxAge);
        }

        if (null !== $sharedAge) {
            $response->setSharedMaxAge($sharedAge);
        }

        if ($private) {
            $response->setPrivate();
        } elseif (false === $private || (null === $private && (null !== $maxAge || null !== $sharedAge))) {
            $response->setPublic();
        }

        if (null !== $reverseProxyTtl) {
            $response->headers->set(SuluHttpCache::HEADER_REVERSE_PROXY_TTL, (string) $reverseProxyTtl);
        }

        return $response;
    }
}

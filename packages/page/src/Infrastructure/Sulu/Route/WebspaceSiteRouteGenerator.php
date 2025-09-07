<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Route;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Route\Application\Routing\Generator\SiteRouteGeneratorInterface;
use Sulu\Route\Domain\Exception\MissingRequestContextParameterException;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

/**
 * @final
 *
 * @internal this class is internal and should not be extended or overwritten
 *
 * @experimental
 */
class WebspaceSiteRouteGenerator implements SiteRouteGeneratorInterface
{
    public function __construct(
        private readonly WebspaceManagerInterface $webspaceManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function generate(RequestContext $requestContext, string $slug, string $locale): string
    {
        $site = $requestContext->getParameter(RequestAttributeEnum::SITE->value);
        if (!\is_string($site)) {
            $currentRequest = $this->requestStack->getCurrentRequest();

            if (!$currentRequest instanceof Request) {
                throw new MissingRequestContextParameterException(RequestAttributeEnum::SITE->value);
            }

            $site = $currentRequest->attributes->get(RequestAttributeEnum::SITE->value); // TODO the requestContext should be kept in sync via listener with request attributes

            if (!\is_string($site)) {
                throw new MissingRequestContextParameterException(RequestAttributeEnum::SITE->value);
            }
        }

        $url = $this->webspaceManager->findUrlByResourceLocator($slug, null, $locale, $site, $requestContext->getHost(), $requestContext->getScheme());

        if (null === $url) {
            throw new \RuntimeException(\sprintf('No url found for "%s" in locale "%s" and site "%s".', $slug, $locale, $site));
        }

        return $url;
    }
}

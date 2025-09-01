<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Userinterface\Controller\Admin;

use Sulu\Route\Application\ResourceLocator\ResourceLocatorGeneratorInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final readonly class ResourceLocatorGenerateController
{
    public function __construct(private ResourceLocatorGeneratorInterface $resourceLocatorGenerator)
    {
    }

    public function __invoke(Request $request): Response
    {
        $resourceLocatorRequest = $this->createResourceLocatorRequestByRequest($request);

        return new JsonResponse([
            'resourceLocator' => $this->resourceLocatorGenerator->generate($resourceLocatorRequest),
        ]);
    }

    private function createResourceLocatorRequestByRequest(Request $request): ResourceLocatorRequest
    {
        $payload = $request->getPayload();

        $parts = \array_filter($payload->all('parts'));
        foreach ($parts as $key => $part) {
            Assert::string($key);
            Assert::string($part);
        }

        /** @var array<string, string>  $parts */
        $locale = $payload->getString('locale');
        $site = $payload->getString('webspace') ?: null;
        $resourceKey = $payload->getString('resourceKey');
        $resourceId = $payload->getString('resourceId') ?: null;
        $parentResourceId = $payload->getString('parentId') ?: null;
        $parentResourceKey = $payload->getString('parentKey') ?: $resourceKey;
        $routeSchema = $payload->getString('routeSchema') ?: null;

        return new ResourceLocatorRequest(
            $parts,
            $locale,
            $site,
            $resourceKey,
            $resourceId,
            $parentResourceId,
            $parentResourceKey,
            $routeSchema,
        );
    }
}

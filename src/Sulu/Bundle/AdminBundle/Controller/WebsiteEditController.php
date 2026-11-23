<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Redirects the edit button on the website to the admin detail view of the shown resource, or to
 * the admin start page when there is no such view or the user cannot access it.
 *
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
final class WebsiteEditController
{
    public function __construct(
        private readonly ResourceViewUrlGeneratorInterface $resourceViewUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function redirectAction(Request $request): RedirectResponse
    {
        return new RedirectResponse($this->generateDetailUrl($request) ?? $this->urlGenerator->generate('sulu_admin'));
    }

    private function generateDetailUrl(Request $request): ?string
    {
        $resourceKey = $request->query->getString('resourceKey');
        $resourceId = $request->query->getString('resourceId');
        if ('' === $resourceKey || '' === $resourceId) {
            return null;
        }

        $viewParameters = \array_filter([
            'id' => $resourceId,
            'locale' => $request->query->getString('locale'),
            'webspace' => $request->query->getString('webspace'),
        ], fn (string $value) => '' !== $value);

        try {
            return $this->resourceViewUrlGenerator->generate($resourceKey, 'detail', $viewParameters);
        } catch (ResourceViewNotFoundException|ViewNotFoundException|ViewParameterNotFoundException) {
            return null;
        }
    }
}

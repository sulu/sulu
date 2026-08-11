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

namespace Sulu\Content\Infrastructure\Symfony\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Renders the HTML attribute the preview navigation JavaScript uses to jump from a clicked
 * element in the preview iframe to the matching field in the admin form. The attribute is only
 * rendered while the current request is an actual Sulu preview render, so it never reaches the
 * public website output.
 */
class PreviewDeepLinkExtension extends AbstractExtension
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_preview_deep_link', $this->renderDeepLinkAttribute(...), ['is_safe' => ['html']]),
        ];
    }

    public function renderDeepLinkAttribute(?string $id): string
    {
        if (!$id) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request || true !== $request->attributes->get('preview', false)) {
            return '';
        }

        // Public/shareable preview links also render with preview=true but have no admin form on
        // the other end to navigate to, so they opt out via this separate attribute (see
        // PreviewRenderer::render()) instead of needlessly exposing block ids.
        if (false === $request->attributes->get('sulu_preview_deep_link', true)) {
            return '';
        }

        return \sprintf('data-sulu-preview-id="%s"', \htmlspecialchars($id, \ENT_QUOTES, 'UTF-8'));
    }
}

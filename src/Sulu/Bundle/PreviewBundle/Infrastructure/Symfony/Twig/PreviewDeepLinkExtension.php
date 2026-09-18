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

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Renders the data-sulu-preview-id attribute the preview navigation uses to jump from a clicked
 * preview element to the matching admin field. Only rendered during an actual Sulu preview render.
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

        return \sprintf('data-sulu-preview-id="%s"', \htmlspecialchars($id, \ENT_QUOTES, 'UTF-8'));
    }
}

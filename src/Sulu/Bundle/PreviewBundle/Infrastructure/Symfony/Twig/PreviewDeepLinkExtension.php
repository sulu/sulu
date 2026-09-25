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
 * preview element to the matching admin field, and the colors of its overlay. Only rendered during
 * an actual Sulu preview render.
 */
class PreviewDeepLinkExtension extends AbstractExtension
{
    /**
     * Maps the accepted color keys to the CSS custom properties the overlay script reads.
     */
    private const COLOR_PROPERTIES = [
        'border' => '--sulu-preview-deep-link-border',
        'icon' => '--sulu-preview-deep-link-icon',
    ];

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_preview_deep_link', $this->renderDeepLinkAttribute(...), ['is_safe' => ['html']]),
            new TwigFunction('sulu_preview_deep_link_colors', $this->renderDeepLinkColors(...), ['is_safe' => ['html']]),
        ];
    }

    public function renderDeepLinkAttribute(?string $id): string
    {
        if (!$id) {
            return '';
        }

        if (!$this->isPreview()) {
            return '';
        }

        return \sprintf('data-sulu-preview-id="%s"', \htmlspecialchars($id, \ENT_QUOTES, 'UTF-8'));
    }

    /**
     * The dark colors apply with a dark prefers-color-scheme.
     *
     * @param array<string, mixed> $light
     * @param array<string, mixed>|null $dark
     */
    public function renderDeepLinkColors(array $light, ?array $dark = null): string
    {
        $lightDeclarations = $this->buildDeclarations($light);
        $darkDeclarations = null !== $dark ? $this->buildDeclarations($dark) : '';

        if (!$this->isPreview()) {
            return '';
        }

        $css = '' !== $lightDeclarations ? \sprintf(':root{%s}', $lightDeclarations) : '';
        if ('' !== $darkDeclarations) {
            $css .= \sprintf('@media (prefers-color-scheme: dark){:root{%s}}', $darkDeclarations);
        }

        if ('' === $css) {
            return '';
        }

        return \sprintf('<style>%s</style>', $css);
    }

    /**
     * @param array<string, mixed> $colors
     */
    private function buildDeclarations(array $colors): string
    {
        $declarations = '';
        foreach ($colors as $key => $color) {
            if (!isset(self::COLOR_PROPERTIES[$key])) {
                throw new \InvalidArgumentException(\sprintf(
                    'Unknown color "%s", expected one of "%s".',
                    $key,
                    \implode('", "', \array_keys(self::COLOR_PROPERTIES))
                ));
            }

            // Restricted to the characters of color values so nothing can break out of the style.
            if (!\is_string($color) || !\preg_match('/^[#a-zA-Z0-9(),.%\s\/+-]+$/', $color)) {
                throw new \InvalidArgumentException(\sprintf('Invalid value for color "%s".', $key));
            }

            $declarations .= \sprintf('%s:%s;', self::COLOR_PROPERTIES[$key], \trim($color));
        }

        return $declarations;
    }

    private function isPreview(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request && true === $request->attributes->get('preview', false);
    }
}

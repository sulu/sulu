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

namespace Sulu\Bundle\AdminBundle\Twig;

use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\AdminBundle\EventListener\WebsiteEditCookieSubscriber;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Renders the script tag of the website edit button. The markup is the same for every visitor so
 * the page stays cacheable; the script itself checks the admin login cookie. The Twig function
 * sulu_admin_edit_button() is experimental.
 *
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
final class WebsiteEditTwigExtension extends AbstractExtension
{
    private const SCRIPT_PATH = '/bundles/suluadmin/js/website-edit-button.js';

    // Admin routes are not loaded in the website kernel; core assumes "/admin" like the session cookie_path.
    private const ENDPOINT_PATH = '/admin/website-edit';

    private const LABEL_KEY = 'sulu_admin.edit_in_admin';

    private const FALLBACK_LOCALE = 'en';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_admin_edit_button', $this->renderEditButton(...), ['is_safe' => ['html']]),
        ];
    }

    public function renderEditButton(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || true === $request->attributes->get('preview', false)) {
            return '';
        }

        $query = [];
        $route = $request->attributes->get('_sulu_route');
        if ($route instanceof Route) {
            $query = \array_filter([
                'resourceKey' => $route->getResourceKey(),
                'resourceId' => $route->getResourceId(),
                'locale' => $route->getLocale(),
                'webspace' => $route->getWebspace(),
            ], fn (?string $value) => null !== $value && '' !== $value);
        }

        return \sprintf(
            '<script src="%s" data-url="%s" data-label="%s" data-cookie="%s" defer></script>',
            self::SCRIPT_PATH,
            $this->escape(self::ENDPOINT_PATH . ([] !== $query ? '?' . \http_build_query($query) : '')),
            $this->escape($this->translateLabel($request->getLocale())),
            WebsiteEditCookieSubscriber::COOKIE_NAME,
        );
    }

    private function translateLabel(string $locale): string
    {
        $label = $this->translator->trans(self::LABEL_KEY, [], AdminController::TRANSLATION_DOMAIN, $locale);

        // Admin translations exist only for the admin languages.
        if (self::LABEL_KEY === $label) {
            $label = $this->translator->trans(self::LABEL_KEY, [], AdminController::TRANSLATION_DOMAIN, self::FALLBACK_LOCALE);
        }

        return $label;
    }

    private function escape(string $value): string
    {
        return \htmlspecialchars($value, \ENT_QUOTES, 'UTF-8');
    }
}

<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\TwoFactor\TwoFactorForceChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Denies access to the admin api while a forced two factor setup was not completed,
 * so the setup can not be skipped by a client that ignores the overlay.
 *
 * @internal
 */
class ForceTwoFactorSetupSubscriber implements EventSubscriberInterface
{
    /**
     * Everything the admin needs to boot and to complete the setup. Missing one of them does not
     * degrade the admin, it stops it from rendering at all, so the list covers the whole bootstrap.
     *
     * Saving the profile is deliberately not part of it. It would let the user change the email the
     * force pattern matches against and walk out of the requirement.
     */
    public const ALLOWED_ROUTES = [
        'sulu_admin',
        'sulu_admin.config',
        'sulu_admin.translation',
        'sulu_admin.metadata',
        'sulu_admin.login_check',
        'sulu_admin.logout',
        'fos_js_routing_js',
        '2fa_login_check_admin',
        'sulu_security.get_profile',
        'sulu_security.patch_profile_settings',
        'sulu_security.delete_profile_settings',
        'sulu_security.post_profile_two-factor_method',
        'sulu_security.post_profile_two-factor_setup',
        'sulu_security.post_profile_two-factor_confirm',
        'sulu_security.post_profile_two-factor_backup-codes',
    ];

    /**
     * Symfony registers its own routes with a leading underscore. The web debug toolbar and the
     * profiler are the ones reached from the admin, and blocking them only produces noise.
     */
    private const INTERNAL_ROUTE_PREFIX = '_';

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private TwoFactorForceChecker $twoFactorForceChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // the firewall runs at priority 8, so the token of the current request is available here
            KernelEvents::REQUEST => ['onKernelRequest', 6],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        if (!\is_string($route)
            || \in_array($route, self::ALLOWED_ROUTES, true)
            || \str_starts_with($route, self::INTERNAL_ROUTE_PREFIX)
        ) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof User || !$this->twoFactorForceChecker->isSetupRequired($user)) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'code' => Response::HTTP_FORBIDDEN,
            'message' => 'Two factor authentication is required for this user and has to be set up first.',
            'error' => 'two_factor_setup_required',
        ], Response::HTTP_FORBIDDEN));
    }
}

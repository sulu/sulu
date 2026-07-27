<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\SecurityBundle\TwoFactor\BackupCodeGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal
 */
class ProfileTwoFactorController
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ObjectManager $objectManager,
        private ViewHandlerInterface $viewHandler,
        private BackupCodeGenerator $backupCodeGenerator,
        private ?TotpAuthenticatorInterface $totpAuthenticator,
        private bool $backupCodesEnabled,
        private ?string $twoFactorForcePattern,
    ) {
    }

    /**
     * Generates a new secret for the authenticator app based "totp" two factor method
     * and returns the content for the QR code to be scanned by the app.
     *
     * The secret is only stored as pending and does not activate the second factor
     * until it was confirmed with a valid code.
     */
    public function postSetupAction(Request $request): Response
    {
        $method = (string) $request->request->get('method');
        $authenticator = $this->getAuthenticator($method);
        $user = $this->getUser();

        if ($method === $user->getTwoFactor()?->getMethod()) {
            return $this->viewHandler->handle(
                View::create(['error' => 'method_already_enabled'], 400),
            );
        }

        $twoFactor = $user->getTwoFactor();
        if (!$twoFactor) {
            $twoFactor = new UserTwoFactor($user);
            $user->setTwoFactor($twoFactor);
            $this->objectManager->persist($twoFactor);
        }

        $options = $twoFactor->getOptions() ?? [];
        $options['pendingTotpSecret'] = $authenticator->generateSecret();
        // a stale secret of a previously disabled setup must not be confirmable
        unset($options['totpSecret']);
        $twoFactor->setOptions($options);

        $this->objectManager->flush();

        return $this->viewHandler->handle(
            View::create([
                'secret' => $options['pendingTotpSecret'],
                'qrContent' => $authenticator->getQRContent($user),
            ]),
        );
    }

    /**
     * Activates the given two factor method for the current user if the given code
     * matches the previously generated secret.
     */
    public function postConfirmAction(Request $request): Response
    {
        $method = (string) $request->request->get('method');
        $authenticator = $this->getAuthenticator($method);
        $user = $this->getUser();

        $twoFactor = $user->getTwoFactor();
        $pendingTotpSecret = $twoFactor?->getOptions()['pendingTotpSecret'] ?? null;
        if (!$pendingTotpSecret) {
            return $this->viewHandler->handle(
                View::create(['error' => 'setup_required'], 400),
            );
        }

        $code = (string) $request->request->get('code');
        if (!$authenticator->checkCode($user, $code)) {
            return $this->viewHandler->handle(
                View::create(['error' => 'invalid_code'], 400),
            );
        }

        $options = $twoFactor->getOptions() ?? [];
        $options['totpSecret'] = $pendingTotpSecret;
        unset($options['pendingTotpSecret']);
        $twoFactor->setOptions($options);
        $twoFactor->setMethod($method);

        $this->objectManager->flush();

        return new Response('', 204);
    }

    /**
     * Regenerates the backup codes of the current user and invalidates the previous ones.
     */
    public function postBackupCodesAction(): Response
    {
        if (!$this->backupCodesEnabled) {
            throw new NotFoundHttpException('Backup codes are not enabled. Install "scheb/2fa-backup-code" and enable it in the "scheb_two_factor" configuration.');
        }

        $user = $this->getUser();

        $twoFactor = $user->getTwoFactor();
        if (!$twoFactor?->getMethod()) {
            return $this->viewHandler->handle(
                View::create(['error' => 'two_factor_not_enabled'], 400),
            );
        }

        $backupCodes = $this->regenerateBackupCodes($twoFactor);

        $this->objectManager->flush();

        return $this->viewHandler->handle(
            View::create(['backupCodes' => $backupCodes]),
        );
    }

    public function deleteAction(): Response
    {
        $user = $this->getUser();

        if ($this->twoFactorForcePattern
            && \preg_match($this->twoFactorForcePattern, $user->getEmail() ?: '')
        ) {
            throw new AccessDeniedHttpException('Two factor authentication is forced for this user and can not be disabled.');
        }

        $twoFactor = $user->getTwoFactor();
        if ($twoFactor) {
            $user->setTwoFactor(null);
            $this->objectManager->remove($twoFactor);
            $this->objectManager->flush();
        }

        return new Response('', 204);
    }

    /**
     * @return string[]
     */
    private function regenerateBackupCodes(UserTwoFactor $twoFactor): array
    {
        $backupCodes = $this->backupCodeGenerator->generate();

        $options = $twoFactor->getOptions() ?? [];
        $options['backupCodes'] = \array_map(
            fn (string $backupCode) => $this->backupCodeGenerator->hash($backupCode),
            $backupCodes,
        );
        $twoFactor->setOptions($options);

        return $backupCodes;
    }

    private function getAuthenticator(string $method): TotpAuthenticatorInterface
    {
        $authenticator = 'totp' === $method ? $this->totpAuthenticator : null;

        if (!$authenticator) {
            throw new NotFoundHttpException(\sprintf(
                'The two factor method "%s" is not available. Install the "scheb/2fa-totp" package and enable it in the "scheb_two_factor" configuration.',
                $method,
            ));
        }

        return $authenticator;
    }

    /**
     * @return User&\Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface
     */
    private function getUser(): User
    {
        /** @var User&\Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user;
    }
}

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
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
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
    /**
     * @internal
     */
    public const SECRET_OPTIONS = [
        'totp' => [
            'secret' => 'totpSecret',
            'pendingSecret' => 'pendingTotpSecret',
            'package' => 'scheb/2fa-totp',
        ],
        'google' => [
            'secret' => 'googleAuthenticatorSecret',
            'pendingSecret' => 'pendingGoogleAuthenticatorSecret',
            'package' => 'scheb/2fa-google-authenticator',
        ],
    ];

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ObjectManager $objectManager,
        private ViewHandlerInterface $viewHandler,
        private BackupCodeGenerator $backupCodeGenerator,
        private ?TotpAuthenticatorInterface $totpAuthenticator,
        private ?GoogleAuthenticatorInterface $googleAuthenticator,
        private ?CodeGeneratorInterface $emailCodeGenerator,
        private bool $backupCodesEnabled,
        private ?string $twoFactorForcePattern,
    ) {
    }

    /**
     * Starts the setup of a two factor method. An authenticator app based method generates a new
     * secret and returns the content for its QR code. The email method instead sends a
     * verification code to the user's email address. Both stay pending until postConfirmAction
     * verifies them, so a wrong code, a lost QR code or an unreachable inbox never locks a user out.
     */
    public function postSetupAction(Request $request): Response
    {
        $method = (string) $request->request->get('method');
        $user = $this->getUser();

        if ('email' === $method) {
            return $this->setupEmail($user);
        }

        return $this->setupAuthenticator($method, $user);
    }

    /**
     * Activates the given two factor method for the current user if the given code
     * matches the previously generated secret or, for email, the code that was sent.
     */
    public function postConfirmAction(Request $request): Response
    {
        $method = (string) $request->request->get('method');
        $user = $this->getUser();
        $code = (string) $request->request->get('code');

        if ('email' === $method) {
            return $this->confirmEmail($user, $code);
        }

        return $this->confirmAuthenticator($method, $user, $code);
    }

    private function setupAuthenticator(string $method, User $user): Response
    {
        $authenticator = $this->getAuthenticator($method);
        $secretOptions = self::SECRET_OPTIONS[$method];

        // only an already confirmed method is rejected, a stale one without its secret has to stay
        // replaceable, otherwise a user whose method lost its secret could never set one up again
        $twoFactor = $user->getTwoFactor();
        if ($method === $twoFactor?->getMethod()
            && ($twoFactor->getOptions()[$secretOptions['secret']] ?? null)
        ) {
            return $this->viewHandler->handle(
                View::create(['error' => 'method_already_enabled'], 400),
            );
        }

        if (!$twoFactor) {
            $twoFactor = new UserTwoFactor($user);
            $user->setTwoFactor($twoFactor);
            $this->objectManager->persist($twoFactor);
        }

        $pendingSecret = $authenticator->generateSecret();

        $options = $twoFactor->getOptions() ?? [];
        $options[$secretOptions['pendingSecret']] = $pendingSecret;
        // a stale secret of a previously disabled setup must not be confirmable
        unset($options[$secretOptions['secret']]);
        $twoFactor->setOptions($options);

        $this->objectManager->flush();

        return $this->viewHandler->handle(
            View::create([
                'secret' => $pendingSecret,
                'qrContent' => $authenticator->getQRContent($user),
            ]),
        );
    }

    private function confirmAuthenticator(string $method, User $user, string $code): Response
    {
        $authenticator = $this->getAuthenticator($method);
        $secretOptions = self::SECRET_OPTIONS[$method];

        $twoFactor = $user->getTwoFactor();
        $options = $twoFactor?->getOptions() ?? [];
        $pendingSecret = $options[$secretOptions['pendingSecret']] ?? null;
        if (!$twoFactor || !$pendingSecret) {
            return $this->viewHandler->handle(
                View::create(['error' => 'setup_required'], 400),
            );
        }

        if (!$authenticator->checkCode($user, $code)) {
            return $this->viewHandler->handle(
                View::create(['error' => 'invalid_code'], 400),
            );
        }

        // only the confirmed method keeps a secret, so no stale secret of another
        // method can be confirmed or activated later anymore
        foreach (self::SECRET_OPTIONS as $methodSecretOptions) {
            unset($options[$methodSecretOptions['secret']], $options[$methodSecretOptions['pendingSecret']]);
        }
        $options[$secretOptions['secret']] = $pendingSecret;
        $twoFactor->setOptions($options);
        $twoFactor->setMethod($method);

        $this->objectManager->flush();

        return new Response('', 204);
    }

    private function setupEmail(User $user): Response
    {
        if (!$this->emailCodeGenerator) {
            throw new NotFoundHttpException('The two factor method "email" is not available. Install "scheb/2fa-email" and enable it in the "scheb_two_factor" configuration.');
        }

        $twoFactor = $user->getTwoFactor();
        if ('email' === $twoFactor?->getMethod()) {
            return $this->viewHandler->handle(
                View::create(['error' => 'method_already_enabled'], 400),
            );
        }

        if (!$twoFactor) {
            $twoFactor = new UserTwoFactor($user);
            $user->setTwoFactor($twoFactor);
            $this->objectManager->persist($twoFactor);
        }

        $this->emailCodeGenerator->generateAndSend($user);
        $this->objectManager->flush();

        return new Response('', 204);
    }

    private function confirmEmail(User $user, string $code): Response
    {
        if (!$this->emailCodeGenerator) {
            throw new NotFoundHttpException('The two factor method "email" is not available. Install "scheb/2fa-email" and enable it in the "scheb_two_factor" configuration.');
        }

        $twoFactor = $user->getTwoFactor();
        $authCode = $user->getEmailAuthCode();
        if (!$twoFactor || null === $authCode) {
            return $this->viewHandler->handle(
                View::create(['error' => 'setup_required'], 400),
            );
        }

        if (!\hash_equals($authCode, \str_replace(' ', '', $code))) {
            return $this->viewHandler->handle(
                View::create(['error' => 'invalid_code'], 400),
            );
        }

        // an authenticator app based setup that was abandoned for email loses its pending secret,
        // so a stale code from it can not be confirmed later anymore
        $options = $twoFactor->getOptions() ?? [];
        foreach (self::SECRET_OPTIONS as $secretOptions) {
            unset($options[$secretOptions['secret']], $options[$secretOptions['pendingSecret']]);
        }
        $twoFactor->setOptions($options);
        $twoFactor->setMethod('email');

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

    private function getAuthenticator(string $method): TotpAuthenticatorInterface|GoogleAuthenticatorInterface
    {
        $authenticator = match ($method) {
            'totp' => $this->totpAuthenticator,
            'google' => $this->googleAuthenticator,
            default => null,
        };

        if (!$authenticator) {
            $package = self::SECRET_OPTIONS[$method]['package'] ?? null;

            throw new NotFoundHttpException($package
                ? \sprintf(
                    'The two factor method "%s" is not available. Install the "%s" package and enable it in the "scheb_two_factor" configuration.',
                    $method,
                    $package,
                )
                : \sprintf('The two factor method "%s" does not support a setup.', $method));
        }

        return $authenticator;
    }

    /**
     * @return User&\Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface&\Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface&\Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface
     */
    private function getUser(): User
    {
        /** @var User&\Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface&\Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface&\Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user;
    }
}

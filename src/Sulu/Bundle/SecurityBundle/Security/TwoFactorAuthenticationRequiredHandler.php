<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Scheb\TwoFactorBundle\Security\Http\Authentication\AuthenticationRequiredHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @internal
 */
class TwoFactorAuthenticationRequiredHandler implements AuthenticationRequiredHandlerInterface
{
    public function onAuthenticationRequired(Request $request, TokenInterface $token): Response
    {
        return new Response(
            '{"error": "2fa_required", "completed": false}',
            401
        );
    }
}

<?php

namespace Sulu\Bundle\SecurityBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * @internal
 */
class TwoFactorAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Return the response to tell the client that 2fa failed. You may want to add more details
        // from the $exception.
        return new Response(
            '{"error": "2fa_failed", "2fa_complete": false}',
            400
        );
    }
}

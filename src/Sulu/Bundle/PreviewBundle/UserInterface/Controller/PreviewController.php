<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\UserInterface\Controller;

use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class PreviewController
{
    public function __construct(
        private Preview $preview,
        private TokenStorageInterface $tokenStorage,
        private ?Profiler $profiler = null,
    ) {
    }

    public function startAction(Request $request): Response
    {
        $id = $request->query->getString('id') ?: throw new MissingParameterException(self::class, 'id');
        $provider = $request->query->getString('provider') ?: throw new MissingParameterException(self::class, 'provider');
        $options = $this->getOptionsFromRequest($request);

        return new JsonResponse(
            [
                'token' => $this->preview->start($provider, $id, $this->getUserId(), [], $options),
            ]
        );
    }

    public function renderAction(Request $request): Response
    {
        $provider = $request->query->getString('provider') ?: throw new MissingParameterException(self::class, 'provider');
        $id = $request->query->getString('id') ?: throw new MissingParameterException(self::class, 'id');
        $token = $request->query->getString('token') ?: throw new MissingParameterException(self::class, 'token');

        $options = $this->getOptionsFromRequest($request);

        if (!$this->preview->exists($token)) {
            $token = $this->preview->start($provider, $id, $this->getUserId(), [], $options);
        }

        $content = $this->preview->render($token, $options);

        $this->disableProfiler();

        return new Response($content, 200, ['Content-Type' => 'text/html']);
    }

    public function updateAction(Request $request): Response
    {
        $provider = $request->query->getString('provider') ?: throw new MissingParameterException(self::class, 'provider');
        $id = $request->query->getString('id') ?: throw new MissingParameterException(self::class, 'id');
        $token = $request->query->getString('token') ?: throw new MissingParameterException(self::class, 'token');

        $payload = $request->getPayload();

        if (!$payload->has('data')) {
            throw new MissingParameterException(self::class, 'data');
        }

        /** @var array<string, mixed> $data */
        $data = $payload->all('data');

        $options = $this->getOptionsFromRequest($request);

        if (!$this->preview->exists($token)) {
            $token = $this->preview->start($provider, $id, $this->getUserId(), [], $options);
        }

        $result = $this->preview->update(
            $token,
            $data,
            $options
        );

        return new JsonResponse($result);
    }

    public function updateContextAction(Request $request): Response
    {
        $provider = $request->query->getString('provider') ?: throw new MissingParameterException(self::class, 'provider');
        $id = $request->query->getString('id') ?: throw new MissingParameterException(self::class, 'id');
        $token = $request->query->getString('token') ?: throw new MissingParameterException(self::class, 'token');

        $payload = $request->getPayload();

        if (!$payload->has('context')) {
            throw new MissingParameterException(self::class, 'context');
        }

        if (!$payload->has('data')) {
            throw new MissingParameterException(self::class, 'data');
        }

        /** @var array<string, mixed> $context */
        $context = $payload->all('context');

        /** @var array<string, mixed> $data */
        $data = $payload->all('data');

        $options = $this->getOptionsFromRequest($request);

        if (!$this->preview->exists($token)) {
            $token = $this->preview->start($provider, $id, $this->getUserId(), [], $options);
        }

        $content = $this->preview->updateContext(
            $token,
            $context,
            $data,
            $options
        );

        return new JsonResponse(['content' => $content]);
    }

    public function stopAction(Request $request): Response
    {
        $token = $request->query->getString('token') ?: throw new MissingParameterException(self::class, 'token');

        $this->preview->stop($token);

        return new JsonResponse();
    }

    private function disableProfiler(): void
    {
        if (!$this->profiler) {
            return;
        }

        $this->profiler->disable();
    }

    private function getUserId(): int
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            throw new AccessDeniedHttpException();
        }

        $user = $token->getUser();
        if (!$user || !$user instanceof UserInterface) {
            throw new AccessDeniedHttpException();
        }

        return $user->getId();
    }

    /**
     * @return array<string, mixed>
     */
    private function getOptionsFromRequest(Request $request): array
    {
        return \array_filter($request->query->all(), function($key) {
            return match ($key) {
                'id', 'provider', 'token' => false,
                default => true,
            };
        }, \ARRAY_FILTER_USE_KEY);
    }
}

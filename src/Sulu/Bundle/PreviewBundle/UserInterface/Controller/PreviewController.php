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

use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PreviewController
{
    use RequestParametersTrait;

    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Profiler|null
     */
    private $profiler;

    public function __construct(
        PreviewInterface $preview,
        TokenStorageInterface $tokenStorage,
        ?Profiler $profiler = null
    ) {
        $this->preview = $preview;
        $this->tokenStorage = $tokenStorage;
        $this->profiler = $profiler;
    }

    public function startAction(Request $request): Response
    {
        $id = $this->getRequestParameter($request, 'id', true);
        $provider = $this->getRequestParameter($request, 'provider', true);
        $options = $this->getOptionsFromRequest($request);

        return new JsonResponse(
            [
                'token' => $this->preview->start($provider, $id, $this->getUserId(), [], $options),
            ]
        );
    }

    public function renderAction(Request $request): Response
    {
        $provider = $this->getRequestParameter($request, 'provider', true);
        $id = $this->getRequestParameter($request, 'id', true);
        $token = $this->getRequestParameter($request, 'token', true);

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
        $provider = $this->getRequestParameter($request, 'provider', true);
        $id = $this->getRequestParameter($request, 'id', true);
        $token = $this->getRequestParameter($request, 'token', true);
        $data = (array) $this->getRequestParameter($request, 'data', true);

        $options = $this->getOptionsFromRequest($request);

        if (!$this->preview->exists($token)) {
            $token = $this->preview->start($provider, $id, $this->getUserId(), [], $options);
        }

        $content = $this->preview->update(
            $token,
            $data,
            $options
        );

        return new JsonResponse(['content' => $content]);
    }

    public function updateContextAction(Request $request): Response
    {
        $id = $this->getRequestParameter($request, 'id', true);
        $provider = $this->getRequestParameter($request, 'provider', true);
        $token = $this->getRequestParameter($request, 'token', true);
        $context = (array) $this->getRequestParameter($request, 'context', true);
        /** @var mixed[] $data */
        $data = $this->getRequestParameter($request, 'data', true);

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
        $this->preview->stop($this->getRequestParameter($request, 'token', true));

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
     * @return mixed[]
     */
    private function getOptionsFromRequest(Request $request): array
    {
        return \array_filter($request->query->all(), function($key) {
            switch ($key) {
                case 'id':
                case 'provider':
                case 'token':
                    return false;
                default:
                    return true;
            }
        }, \ARRAY_FILTER_USE_KEY);
    }
}

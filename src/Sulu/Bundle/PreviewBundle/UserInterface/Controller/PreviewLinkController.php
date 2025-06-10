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

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\PreviewBundle\Application\Manager\PreviewLinkManagerInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class PreviewLinkController extends AbstractRestController
{
    use RequestParametersTrait;

    public function __construct(
        private PreviewLinkRepositoryInterface $previewLinkRepository,
        private PreviewLinkManagerInterface $previewLinkManager,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function getAction(Request $request, string $resourceId): Response
    {
        $resourceKey = $this->getRequestParameter($request, 'resourceKey', true);
        $locale = $this->getRequestParameter($request, 'locale', true);

        $previewLink = $this->previewLinkRepository->findByResource($resourceKey, $resourceId, $locale);
        if (!$previewLink) {
            return $this->handleView($this->view(null, 404));
        }

        return $this->handleView($this->view($previewLink));
    }

    public function postTriggerAction(Request $request, string $resourceId): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);

        try {
            switch ($action) {
                case 'generate':
                    $resourceKey = $this->getRequestParameter($request, 'resourceKey', true);
                    $locale = $this->getRequestParameter($request, 'locale', true);
                    $options = $request->query->all();
                    unset($options['action'], $options['resourceKey'], $options['provider'], $options['locale']);

                    $previewLink = $this->previewLinkManager->generate($resourceKey, $resourceId, $locale, $options);

                    return $this->handleView($this->view($previewLink, 201));
                case 'revoke':
                    $resourceKey = $this->getRequestParameter($request, 'resourceKey', true);
                    $locale = $this->getRequestParameter($request, 'locale', true);

                    $this->previewLinkManager->revoke($resourceKey, $resourceId, $locale);

                    return $this->handleView($this->view(null, 204));
                default:
                    throw new RestException(\sprintf('Unrecognized action: "%s"', $action));
            }
        } catch (RestException $ex) {
            return $this->handleView($this->view($ex->toArray(), 400));
        }
    }
}

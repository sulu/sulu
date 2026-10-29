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
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\PreviewBundle\Application\Manager\PreviewLinkManagerInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("preview-link")
 */
class PreviewLinkController extends AbstractRestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    public function __construct(
        private PreviewLinkRepositoryInterface $previewLinkRepository,
        private PreviewLinkManagerInterface $previewLinkManager,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null,
        private ?PreviewObjectProviderRegistryInterface $previewObjectProviderRegistry = null,
        private ?SecurityCheckerInterface $securityChecker = null,
    ) {
        parent::__construct($viewHandler, $tokenStorage);

        if (null === $this->previewObjectProviderRegistry) {
            @trigger_deprecation('sulu/sulu', '2.6.28', 'Initializing "' . __CLASS__ . '" without previewObjectProviderRegistry is deprecated, reading a preview link is not checked for permissions without it.');
        }
    }

    public function getAction(Request $request, string $resourceId): Response
    {
        $resourceKey = $this->getRequestParameter($request, 'resourceKey', true);
        $locale = $this->getRequestParameter($request, 'locale', true);

        if (null !== $this->previewObjectProviderRegistry && !$this->previewObjectProviderRegistry->hasPreviewObjectProvider($resourceKey)) {
            return $this->handleView($this->view(null, 404));
        }

        $this->checkViewPermission($resourceKey, $resourceId, $locale);

        $previewLink = $this->previewLinkRepository->findByResource($resourceKey, $resourceId, $locale);
        if (!$previewLink) {
            return $this->handleView($this->view(null, 404));
        }

        return $this->handleView($this->view($previewLink));
    }

    public function postTriggerAction(Request $request, string $resourceId): Response
    {
        $action = $request->query->get('action') ?: throw new MissingParameterException(self::class, 'action');

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

    private function checkViewPermission(string $resourceKey, string $resourceId, string $locale): void
    {
        if (null === $this->previewObjectProviderRegistry || null === $this->securityChecker) {
            return;
        }

        $provider = $this->previewObjectProviderRegistry->getPreviewObjectProvider($resourceKey);
        $securityContext = $provider->getSecurityContext($resourceId, $locale);

        if (null === $securityContext) {
            return;
        }

        $securedObject = $provider->getObject($resourceId, $locale) instanceof SecurityBehavior;

        $this->securityChecker->checkPermission(
            new SecurityCondition(
                $securityContext,
                $locale,
                $securedObject ? SecurityBehavior::class : null,
                $securedObject ? $resourceId : null
            ),
            PermissionTypes::VIEW
        );
    }
}

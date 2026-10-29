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
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class PreviewLinkController extends AbstractRestController
{
    public function __construct(
        private PreviewLinkRepositoryInterface $previewLinkRepository,
        private PreviewLinkManagerInterface $previewLinkManager,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null,
        private ?PreviewObjectProviderRegistryInterface $previewObjectProviderRegistry = null,
        private ?SecurityCheckerInterface $securityChecker = null,
    ) {
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function getAction(Request $request, string $resourceId): Response
    {
        $resourceKey = $request->query->getString('resourceKey') ?: throw new MissingParameterException(self::class, 'resourceKey');
        $locale = $this->getLocale($request) ?: throw new MissingParameterException(self::class, 'locale');

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
        $action = $request->query->getString('action') ?: throw new MissingParameterException(self::class, 'action');
        $resourceKey = $request->query->getString('resourceKey') ?: throw new MissingParameterException(self::class, 'resourceKey');
        $locale = $this->getLocale($request) ?: throw new MissingParameterException(self::class, 'locale');

        try {
            switch ($action) {
                case 'generate':
                    $options = $request->query->all();
                    unset($options['action'], $options['resourceKey'], $options['provider'], $options['locale']);

                    $previewLink = $this->previewLinkManager->generate($resourceKey, $resourceId, $locale, $options);

                    return $this->handleView($this->view($previewLink, 201));
                case 'revoke':
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

        $securityContext = $this->previewObjectProviderRegistry
            ->getPreviewObjectProvider($resourceKey)
            ->getSecurityContext(new PreviewContext($resourceId, $locale));

        if (null === $securityContext) {
            return;
        }

        $this->securityChecker->checkPermission(new SecurityCondition($securityContext, $locale), PermissionTypes::VIEW);
    }
}

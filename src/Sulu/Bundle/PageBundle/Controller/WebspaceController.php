<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

@trigger_deprecation(
    'sulu/sulu',
    '2.0',
    'The "%s" class is deprecated, use data from "%s" instead.',
    WebspaceController::class,
    AdminController::class
);

/**
 * @deprecated Deprecated since Sulu 2.0, use data from Sulu\Bundle\AdminBundle\Controller\AdminController::configAction
 * Remember deleting the resource configuration from Sulu\Bundle\AdminBundle\DependencyInjection\SuluAdminExtension.
 */
class WebspaceController extends AbstractRestController implements SecuredControllerInterface
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private WebspaceManagerInterface $webspaceManager,
        private SecurityCheckerInterface $securityChecker,
        private RequestStack $requestStack
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Returns webspaces.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $checkForPermissions = $request->get('checkForPermissions', true);
        $locale = $this->getRequestParameter($request, 'locale', true);
        $webspaces = [];

        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            if ($checkForPermissions) {
                $securityContext = $this->getSecurityContextByWebspace($webspace->getKey());
                $condition = new SecurityCondition($securityContext);

                if (!$this->securityChecker->hasPermission($condition, PermissionTypes::VIEW)) {
                    continue;
                }
            }

            $webspaces[] = $webspace;
        }

        $context = new Context();
        $context->setAttribute('locale', $locale);
        $view = $this->view(new CollectionRepresentation($webspaces, 'webspaces'));
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Returns webspace config by key.
     *
     * @param string $webspaceKey
     *
     * @return Response
     */
    public function getAction($webspaceKey)
    {
        return $this->handleView(
            $this->view($this->webspaceManager->findWebspaceByKey($webspaceKey))
        );
    }

    public function getSecurityContext()
    {
        $request = $this->requestStack->getCurrentRequest();
        $webspaceKey = $request ? $request->get('webspaceKey') : null;

        if (null !== $webspaceKey) {
            return $this->getSecurityContextByWebspace($webspaceKey);
        }

        return null;
    }

    /**
     * Returns security-context by webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    private function getSecurityContextByWebspace($webspaceKey)
    {
        return PageAdmin::SECURITY_CONTEXT_PREFIX . $webspaceKey;
    }
}

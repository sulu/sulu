<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Listens on the kernel.controller event and checks if Sulu allows this action.
 */
class SuluSecurityListener implements EventSubscriberInterface
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    /**
     * Checks if the action is allowed for the current user, and throws an Exception otherwise.
     *
     * @throws AccessDeniedException
     */
    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();
        $action = '__invoke';

        if (\is_array($controller)) {
            if (isset($controller[1])) {
                $action = $controller[1];
            }

            if (isset($controller[0])) {
                $controller = $controller[0];
            }
        }

        if (
            !$controller instanceof SecuredControllerInterface
            && !$controller instanceof SecuredObjectControllerInterface
        ) {
            return;
        }

        $request = $event->getRequest();

        // find appropriate permission type for request
        $permission = '';

        switch ($request->getMethod()) {
            case 'GET':
                $permission = PermissionTypes::VIEW;
                break;
            case 'POST':
                if (\in_array($action, ['postAction', '__invoke'])) { // means that the ClassResourceInterface has to be used
                    $permission = PermissionTypes::ADD;
                } else {
                    $permission = PermissionTypes::EDIT;
                }
                break;
            case 'PUT':
            case 'PATCH':
                $permission = PermissionTypes::EDIT;
                break;
            case 'DELETE':
                $permission = PermissionTypes::DELETE;
                break;
        }

        $securityContext = null;
        $locale = $controller->getLocale($request);
        $objectType = null;
        $objectId = null;

        if ($controller instanceof SecuredObjectControllerInterface) {
            $objectType = $controller->getSecuredClass();
            $objectId = $controller->getSecuredObjectId($request);
        }

        // check permission
        if ($controller instanceof SecuredControllerInterface) {
            $securityContext = $controller->getSecurityContext();
        }

        if (null !== $securityContext) {
            $this->securityChecker->checkPermission(
                new SecurityCondition($securityContext, $locale, $objectType, $objectId),
                $permission
            );
        }
    }
}

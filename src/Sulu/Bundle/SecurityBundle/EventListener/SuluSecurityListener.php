<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Listens on the kernel.controller event and checks if Sulu allows this action
 * @package Sulu\Bundle\SecurityBundle\EventListener
 */
class SuluSecurityListener
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker)
    {
        $this->securityChecker = $securityChecker;
    }

    /**
     * Checks if the action is allowed for the current user, and throws an Exception otherwise
     * @param FilterControllerEvent $event
     * @throws AccessDeniedException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_subclass_of($controller[0], 'Sulu\Component\Rest\RestController')) {
            // TODO check for some kind of security controller interface instead
            return;
        }

        // get subject
        $subject = '';

        // find appropriate permission type for request
        $permission = '';

        switch ($event->getRequest()->getMethod()) {
            case 'GET':
                $permission = 'view';
                break;
            case 'POST':
                if ($controller[1] == 'postAction') { // means that the ClassResourceInterface has to be used
                    $permission = 'add';
                } else {
                    $permission = 'edit';
                }
                break;
            case 'PUT':
                $permission = 'edit';
                break;
            case 'DELETE':
                $permission = 'delete';
                break;
        }

        // TODO find locale for check

        // check permission
        $this->securityChecker->checkPermission($subject, $permission);
    }
} 

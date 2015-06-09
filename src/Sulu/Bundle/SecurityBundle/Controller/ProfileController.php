<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This controller handles everything a user is allowed to change on its own.
 */
class ProfileController implements ClassResourceInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param ObjectManager $objectManager
     * @param ViewHandlerInterface $viewHandler
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ObjectManager $objectManager,
        ViewHandlerInterface $viewHandler
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->objectManager = $objectManager;
        $this->viewHandler = $viewHandler;
    }

    /**
     * Sets the given language on the current user
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putLanguageAction(Request $request)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $user->setLocale($request->get('locale'));

        $this->objectManager->flush();

        return $this->viewHandler->handle(View::create($user));
    }
}

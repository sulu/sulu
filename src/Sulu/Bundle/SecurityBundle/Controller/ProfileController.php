<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\Authentication\UserSettingRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This controller handles everything a user is allowed to change on its own.
 */
class ProfileController implements ClassResourceInterface
{
    protected static $entityNameUserSetting = 'SuluSecurityBundle:UserSetting';

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
     * @var UserSettingRepositoryInterface
     */
    private $userSettingRepository;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var string
     */
    private $userClass;

    /**
     * @var string
     */
    private $contactClass;

    /**
     * @param UserManagerInterface $userManager
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ObjectManager $objectManager,
        ViewHandlerInterface $viewHandler,
        UserSettingRepositoryInterface $userSettingRepository,
        UserManager $userManager,
        string $userClass,
        string $contactClass
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->objectManager = $objectManager;
        $this->viewHandler = $viewHandler;
        $this->userSettingRepository = $userSettingRepository;
        $this->userManager = $userManager;
        $this->userClass = $userClass;
        $this->contactClass = $contactClass;
    }

    /**
     * Gets the profile information of a user.
     *
     * @return Response
     */
    public function getAction()
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $view = View::create($user);

        $context = new Context();
        $context->setGroups(['profile']);

        $view->setContext($context);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the given profile information of a user.
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function putAction(Request $request)
    {
        $this->checkArguments($request);
        $user = $this->tokenStorage->getToken()->getUser();
        $this->userManager->save($request->request->all(), $request->get('locale'), $user->getId(), true);

        $user->setFirstName($request->get('firstName'));
        $user->setLastName($request->get('lastName'));

        $this->objectManager->flush();

        $view = View::create($user);

        $context = new Context();
        $context->setGroups(['profile']);

        $view->setContext($context);

        return $this->viewHandler->handle($view);
    }

    /**
     * Takes a key, value pair and stores it as settings for the user.
     *
     * @return Response
     */
    public function patchSettingsAction(Request $request)
    {
        $settings = $request->request->all();

        try {
            $user = $this->tokenStorage->getToken()->getUser();

            foreach ($settings as $settingKey => $settingValue) {
                // get setting
                // TODO: move this logic into own service (UserSettingManager?)
                $setting = $this->userSettingRepository->findOneBy(['user' => $user, 'key' => $settingKey]);

                // or create new one
                if (!$setting) {
                    $setting = new UserSetting();
                    $setting->setKey($settingKey);
                    $setting->setUser($user);
                    $this->objectManager->persist($setting);
                }

                // persist setting
                $setting->setValue(\json_encode($settingValue));
            }
            $this->objectManager->flush();

            //create view
            $view = View::create($settings, 200);
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes a user setting by a given key.
     *
     * @return Response
     */
    public function deleteSettingsAction(Request $request)
    {
        $key = $request->get('key');

        try {
            if (!$key) {
                throw new MissingArgumentException(static::$entityNameUserSetting, 'key');
            }

            $user = $this->tokenStorage->getToken()->getUser();

            // get setting
            // TODO: move this logic into own service (UserSettingManager?)
            $setting = $this->userSettingRepository->findOneBy(['user' => $user, 'key' => $key]);

            if ($setting) {
                $this->objectManager->remove($setting);
                $this->objectManager->flush();
                $view = View::create(null, 204);
            } else {
                $view = View::create(null, 400);
            }
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Checks the arguments of the given request.
     *
     * @throws MissingArgumentException
     */
    private function checkArguments(Request $request)
    {
        if (null === $request->get('firstName')) {
            throw new MissingArgumentException($this->contactClass, 'firstName');
        }
        if (null === $request->get('lastName')) {
            throw new MissingArgumentException($this->contactClass, 'lastName');
        }
        if (null === $request->get('username')) {
            throw new MissingArgumentException($this->userClass, 'username');
        }
        if (null === $request->get('email')) {
            throw new MissingArgumentException($this->userClass, 'email');
        }
        if (null === $request->get('locale')) {
            throw new MissingArgumentException($this->userClass, 'locale');
        }
    }
}

<?php

/*
 * This file is part of Sulu.
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
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
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
     * @param TokenStorageInterface          $tokenStorage
     * @param ObjectManager                  $objectManager
     * @param ViewHandlerInterface           $viewHandler
     * @param UserSettingRepositoryInterface $userSettingRepository
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ObjectManager $objectManager,
        ViewHandlerInterface $viewHandler,
        UserSettingRepositoryInterface $userSettingRepository
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->objectManager = $objectManager;
        $this->viewHandler = $viewHandler;
        $this->userSettingRepository = $userSettingRepository;
    }

    /**
     * Sets the given language on the current user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function putLanguageAction(Request $request)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $user->setLocale($request->get('locale'));

        $this->objectManager->flush();

        return $this->viewHandler->handle(View::create(['locale' => $user->getLocale()]));
    }

    /**
     * Takes a key, value pair and stores it as settings for the user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function putSettingsAction(Request $request)
    {
        $key = $request->get('key');
        $value = $request->get('value');

        try {
            if (!$key) {
                throw new MissingArgumentException(static::$entityNameUserSetting, 'key');
            }

            if (!$value) {
                throw new MissingArgumentException(static::$entityNameUserSetting, 'value');
            }

            $user = $this->tokenStorage->getToken()->getUser();

            // encode before persist
            $data = json_encode($value);

            // get setting
            // TODO: move this logic into own service (UserSettingManager?)
            $setting = $this->userSettingRepository->findOneBy(['user' => $user, 'key' => $key]);

            // or create new one
            if (!$setting) {
                $setting = new UserSetting();
                $setting->setKey($key);
                $setting->setUser($user);
                $this->objectManager->persist($setting);
            }

            // persist setting
            $setting->setValue($data);
            $this->objectManager->flush();

            //create view
            $view = View::create($setting, 200);
        } catch (RestException $exc) {
            $view = View::create($exc->toArray(), 400);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes a user setting by a given key.
     *
     * @param Request $request
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
}

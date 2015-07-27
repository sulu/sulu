<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\UserManager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Sulu\Bundle\AdminBundle\UserManager\CurrentUserDataInterface;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class CurrentUserData implements CurrentUserDataInterface
{
    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $security;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $registry;

    /**
     * @param SecurityContextInterface                 $security
     * @param RouterInterface                          $router
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $registry
     */
    public function __construct(SecurityContextInterface $security, RouterInterface $router, Registry $registry)
    {
        $this->security = $security;
        $this->router = $router;
        $this->registry = $registry;
    }

    /**
     * @return Boolean - returns if a user is logged in
     */
    public function isLoggedIn()
    {
        if ($this->getUser() && $this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * returns id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->getUser()->getId();
    }

    /**
     * @return String - returns username
     */
    public function getUserName()
    {
        return $this->getUser()->getUsername();
    }

    /**
     * returns fullName.
     *
     * @return String
     */
    public function getFullName()
    {
        return $this->getUser()->getFullName();
    }

    /**
     * @return String - returns UserIcon URL
     */
    public function getUserIcon()
    {
        // TODO: Implement getUserIcon() method.
    }

    /**
     * returns locale of current user.
     *
     * @return String
     */
    public function getLocale()
    {
        return $this->getUser()->getLocale();
    }

    /**
     * returns the settings saved for a user.
     *
     * @return mixed
     */
    public function getUserSettings()
    {
        $settingsArray = [];
        foreach ($this->getUser()->getUserSettings()->toArray() as $setting) {
            $settingsArray[$setting->getKey()] = json_decode($setting->getValue());
        };

        return $settingsArray;
    }

    /**
     * persists the user data to the database.
     *
     * @param $key
     * @param $value
     */
    public function setUserSetting($key, $value)
    {
        $em = $this->registry->getManager();
        $user = $this->getUser();
        // encode before persist
        $data = json_encode($value);

        // get setting
        /** @var UserSetting $setting */
        $setting = $this->registry
            ->getRepository('SuluSecurityBundle:UserSetting')
            ->findOneBy(['user' => $user, 'key' => $key]);

        // or create new one
        if (!$setting) {
            $setting = new UserSetting();
            $setting->setKey($key);
            $setting->setUser($user);
            $em->persist($setting);
        }
        // persist setting
        $setting->setValue($data);
        $em->flush($setting);
    }

    /**
     * Get a user from the Security Context.
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    protected function getUser()
    {
        if (!$this->security) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->security->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            return;
        }

        return $user;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'username' => $this->getUserName(),
            'fullname' => $this->getFullName(),
            'icon' => $this->getUserIcon(),
            'locale' => $this->getLocale(),
            'settings' => json_encode($this->getUserSettings()),
            'contact' => [
                'id' => $this->getUser()->getContact()->getId(),
            ],
        ];
    }
}

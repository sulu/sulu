<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\UserSettingManager;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Component\Security\Authentication\UserSettingRepositoryInterface;

/**
 * Handles operations on settings for multiple or single users
 */
class UserSettingManager implements UserSettingManagerInterface
{
    /**
     * @var UserSettingRepositoryInterface
     */
    protected $repository;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @param ObjectManager $em
     * @param UserSettingRepositoryInterface $repository
     */
    function __construct(ObjectManager $em, UserSettingRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    /**
     * Removes setting for all users by key and value
     *
     * @param string $key
     * @param mixed $value
     */
    public function removeSettings($key, $value)
    {
        $settings = $this->repository->getSettingsByKeyAndValue($key, $value);
        if ($settings && count($settings) > 0) {
            foreach ($settings as $setting) {
                $this->em->remove($setting);
            }
            $this->em->flush();
        }
    }
}

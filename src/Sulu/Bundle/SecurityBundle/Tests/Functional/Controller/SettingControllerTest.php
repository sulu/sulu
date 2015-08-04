<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Sulu\Bundle\SecurityBundle\Entity\User;

class SettingControllerTest extends SuluTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var UserSetting
     */
    protected $setting1;

    /**
     * @var UserSetting
     */
    protected $setting2;

    /**
     * @var User
     */
    protected $user1;
    /**
     * @var User
     */
    protected $user2;

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();
        $this->client = $this->createAuthenticatedClient();

        $this->user1 = new User();
        $this->user1->setUsername('user1');
        $this->user1->setEmail('user1@test1.com');
        $this->user1->setPassword('securepassword');
        $this->user1->setSalt('salt');
        $this->user1->setLocale('de');
        $this->em->persist($this->user1);

        $this->user2 = new User();
        $this->user2->setUsername('user2');
        $this->user2->setEmail('user2@test2.com');
        $this->user2->setPassword('securepassword');
        $this->user2->setSalt('salt');
        $this->user2->setLocale('de');
        $this->em->persist($this->user2);
        $this->em->flush();

        $this->setting1 = new UserSetting();
        $this->setting1->setKey('setting-key');
        $this->setting1->setValue('setting-value');
        $this->setting1->setUser($this->user1);
        $this->em->persist($this->setting1);

        $this->setting2 = new UserSetting();
        $this->setting2->setKey('setting-key');
        $this->setting2->setValue('setting-value');
        $this->setting2->setUser($this->user2);
        $this->em->persist($this->setting2);
        $this->em->flush();
    }

    public function testDeleteSettingsExisting()
    {
        $settingResult1 = $this->getSettingForByKeyAndUser(
            $this->user1,
            'setting-key'
        );

        $settingResult2 = $this->getSettingForByKeyAndUser(
            $this->user2,
            'setting-key'
        );

        $this->assertEquals($this->setting1->getKey(), $settingResult1->getKey());
        $this->assertEquals($this->setting1->getValue(), $settingResult1->getValue());
        $this->assertEquals($this->setting2->getKey(), $settingResult2->getKey());
        $this->assertEquals($this->setting2->getValue(), $settingResult2->getValue());

        $this->client->request(
            'DELETE',
            '/api/settings',
            ['key' => 'setting-key', 'value' => 'setting-value']
        );

        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $settingResult1 = $this->getSettingForByKeyAndUser(
            $this->user1,
            'setting-key'
        );

        $settingResult2 = $this->getSettingForByKeyAndUser (
            $this->user2,
            'setting-key'
        );
        $this->assertNull($settingResult1);
        $this->assertNull($settingResult2);
    }

    public function testDeleteSettingsNotExisting()
    {
        $this->client->request(
            'DELETE',
            '/api/settings',
            ['key' => 'setting-key666', 'value' => 'setting-value666']
        );

        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $settingResult1 = $this->getSettingForByKeyAndUser(
            $this->user1,
            'setting-key'
        );

        $settingResult2 = $this->getSettingForByKeyAndUser (
            $this->user2,
            'setting-key'
        );
        $this->assertNotNull($settingResult1);
        $this->assertNotNull($settingResult2);
    }

    /**
     * Creates a setting for the user associated with the client
     *
     * @param string $key
     * @param mixed $value
     */
    protected function createSettingForUser($key, $value)
    {
        $this->client->request(
            'PUT',
            '/security/profile/settings',
            ['key' => $key, 'value' => $value]
        );
    }

    /**
     * Returns a setting for a user and a key
     *
     * @param User $user
     * @param string $key
     *
     * @return UserSetting|null
     */
    protected function getSettingForByKeyAndUser($user, $key)
    {
        return $this->client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $user,
                'key' => $key,
            ]
        );
    }
}

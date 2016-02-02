<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ProfileControllerTest extends SuluTestCase
{
    public function testChangeLanguageAction()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('PUT', '/security/profile/language', ['locale' => 'de']);

        // check current instance
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $this->assertEquals('de', $user->getLocale());

        // check value in database
        $user = $this->getContainer()->get('test_user_provider')->loadUserByUsername('test');
        $this->assertEquals('de', $user->getLocale());
    }

    public function testPutSettings()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/security/profile/settings',
            ['key' => 'setting-key', 'value' => 'setting-value']
        );

        $userSetting = $client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertEquals('setting-key', $userSetting->getKey());
        $this->assertEquals('setting-value', json_decode($userSetting->getValue()));
    }

    public function testDeleteSettings()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/security/profile/settings',
            ['key' => 'setting-key', 'value' => 'setting-value']
        );

        $userSetting = $client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertEquals('setting-key', $userSetting->getKey());
        $this->assertEquals('setting-value', json_decode($userSetting->getValue()));

        $client->request(
            'DELETE',
            '/security/profile/settings',
            ['key' => 'setting-key']
        );

        $userSetting = $client->getContainer()->get('sulu_security.user_setting_repository')->findOneBy(
            [
                'user' => $client->getContainer()->get('security.token_storage')->getToken()->getUser(),
                'key' => 'setting-key',
            ]
        );

        $this->assertNull($userSetting);
    }
}

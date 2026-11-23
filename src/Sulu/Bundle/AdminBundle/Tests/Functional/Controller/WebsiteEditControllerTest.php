<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Sulu\Bundle\AdminBundle\EventListener\WebsiteEditCookieSubscriber;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class WebsiteEditControllerTest extends SuluTestCase
{
    public function testRedirectsToDetailView(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/website-edit', [
            'resourceKey' => 'pages',
            'resourceId' => '123-abc',
            'locale' => 'en',
            'webspace' => 'sulu_io',
        ]);

        $response = $client->getResponse();
        $this->assertHttpStatusCode(302, $response);
        $this->assertSame('/admin/#/webspaces/sulu_io/pages/en/123-abc', $response->headers->get('Location'));
    }

    public function testRedirectsToStartPageWithoutTarget(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/website-edit');

        $response = $client->getResponse();
        $this->assertHttpStatusCode(302, $response);
        $this->assertSame('/admin/', $response->headers->get('Location'));
    }

    public function testRejectsAnonymousRequest(): void
    {
        $client = $this->createClient();
        $client->request('GET', '/admin/website-edit', ['resourceKey' => 'pages', 'resourceId' => '123-abc']);

        $this->assertHttpStatusCode(401, $client->getResponse());
    }

    public function testRejectsPost(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/admin/website-edit');

        $this->assertHttpStatusCode(405, $client->getResponse());
    }

    public function testLoginSetsAndLogoutClearsCookie(): void
    {
        $client = $this->createClient();
        $this->getTestUser();

        $client->request('POST', '/admin/login', ['_username' => 'test', '_password' => 'test']);
        $cookie = $this->findCookie($client->getResponse());
        $this->assertNotNull($cookie, 'The login response must set the website edit cookie.');
        $this->assertSame('/', $cookie->getPath());
        $this->assertFalse($cookie->isCleared());

        $client->request('GET', '/admin/logout');
        $cookie = $this->findCookie($client->getResponse());
        $this->assertNotNull($cookie, 'The logout response must clear the website edit cookie.');
        $this->assertTrue($cookie->isCleared());
    }

    private function findCookie(Response $response): ?Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if (WebsiteEditCookieSubscriber::COOKIE_NAME === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }
}

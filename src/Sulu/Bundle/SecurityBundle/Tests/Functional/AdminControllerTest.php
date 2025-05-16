<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    public function testRouteConfig(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $routeConfig = $response->sulu_admin->routes;

        $formRoute = null;
        foreach ($routeConfig as $route) {
            if ('sulu_security.form.permissions' === $route->name) {
                $formRoute = $route;
                break;
            }
        }

        $this->assertEquals('User locked', $formRoute->options->toolbarActions[2]->options->label);

        $securityContextConfig = $response->sulu_security->securityContexts;

        $this->assertEquals(
            ['view', 'add', 'edit', 'delete', 'security'],
            $securityContextConfig->Sulu->Media->{'sulu.media.collections'}
        );
    }

    public function testUserMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/user_details');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertSnapshot('user_details.json', $client->getResponse()->getContent() ?: '');
    }

    public function testRoleMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/role_details');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertSnapshot('role_details.json', $client->getResponse()->getContent() ?: '');
    }
}

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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
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
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('username', $form);
        $this->assertObjectHasAttribute('password', $form);

        $schema = $response->schema;

        $this->assertEquals(['username', 'locale'], $schema->allOf[0]->required);
        $this->assertEquals(['id'], $schema->allOf[1]->anyOf[0]->required);
        $this->assertEquals(['password'], $schema->allOf[1]->anyOf[1]->required);
    }

    public function testRoleMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/role_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('name', $form);
        $this->assertObjectHasAttribute('system', $form);

        $schema = $response->schema;

        $this->assertEquals(['name', 'system'], $schema->required);
    }
}

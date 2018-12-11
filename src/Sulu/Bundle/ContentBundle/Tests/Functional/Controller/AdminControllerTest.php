<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function testPageSeoMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/pages_seo');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('search_result', $response);
        $this->assertObjectHasAttribute('title', $response);
        $this->assertObjectHasAttribute('description', $response);
    }

    public function testPageExcerptMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/pages_excerpt');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('title', $response);
        $this->assertObjectHasAttribute('more', $response);
        $this->assertObjectHasAttribute('description', $response);
    }

    public function testPageSettingMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/pages_settings');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('navContexts', $response);
        $this->assertObjectHasAttribute('pageType', $response);
        $this->assertObjectHasAttribute('shadowPage', $response);
    }
}

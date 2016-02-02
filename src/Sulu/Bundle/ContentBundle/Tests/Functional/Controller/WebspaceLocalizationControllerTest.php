<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class WebspaceLocalizationControllerTest extends SuluTestCase
{
    public function testCgetAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/webspace/localizations?webspace=sulu_io');
        $response = json_decode($client->getResponse()->getContent(), true);

        $data = $response['_embedded']['localizations'];

        $filterKeys = ['localization'];

        $filteredData = array_map(
            function ($value) use ($filterKeys) {
                return array_intersect_key($value, array_flip($filterKeys));
            },
            $data
        );

        $this->assertContains(['localization' => 'en'], $filteredData);
        $this->assertContains(['localization' => 'en_us'], $filteredData);
        $this->assertContains(['localization' => 'de'], $filteredData);
        $this->assertContains(['localization' => 'de_at'], $filteredData);
    }

    public function testCgetActionWithNotExistingWebspace()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/webspace/localizations?webspace=sulu_lo');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(0, $response->code);
        $this->assertEquals('No webspace found for key \'sulu_lo\'', $response->message);
    }
}

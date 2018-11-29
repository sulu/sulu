<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class FormatControllerTest extends SuluTestCase
{
    /**
     * @var FormatOptions[]
     */
    private $formatOptions;

    public function testCGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/formats?locale=de');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $formats = $response->_embedded->formats;

        $this->assertCount(13, $formats);
        $this->assertTrue($formats[0]->internal);
        $this->assertEquals('sulu-400x400', $formats[0]->key);
        $this->assertEquals('Kontaktavatar (Sulu)', $formats[0]->title);
        $this->assertEquals(
            [
                'x' => 400,
                'y' => 400,
                'mode' => 'outbound',
                'retina' => false,
                'forceRatio' => true,
            ],
            (array) $formats[0]->scale
        );
    }

    public function testCGetWithoutLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/formats');

        $this->assertHttpStatusCode(400, $client->getResponse());
    }
}

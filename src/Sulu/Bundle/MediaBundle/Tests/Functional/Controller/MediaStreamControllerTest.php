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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class MediaStreamControllerTest extends SuluTestCase
{
    public function testGetImageActionForNonExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/uploads/media/400x400/01/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDownloadActionForNonExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/media/999/download/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $client->getResponse());
    }
}

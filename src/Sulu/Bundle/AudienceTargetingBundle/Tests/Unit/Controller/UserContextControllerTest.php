<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit;

use FOS\HttpCache\UserContext\HashGenerator;
use Sulu\Bundle\AudienceTargetingBundle\Controller\UserContextController;

class UserContextControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HashGenerator
     */
    private $hashGenerator;

    public function setUp()
    {
        $this->hashGenerator = $this->prophesize(HashGenerator::class);
    }

    /**
     * @dataProvider provideConfiguration
     */
    public function testHashAction($header, $lifetime)
    {
        $this->hashGenerator->generateHash()->willReturn('hash');
        $userContextController = new UserContextController($this->hashGenerator->reveal(), $header, $lifetime);
        $response = $userContextController->hashAction();

        $this->assertEquals('hash', $response->headers->get($header));
        $this->assertEquals($lifetime, $response->getMaxAge());
        $this->assertEquals('application/vnd.fos.user-context-hash', $response->headers->get('Content-Type'));
        $this->assertEquals('cookie', $response->headers->get('Vary'));
    }

    public function provideConfiguration()
    {
        return [
            ['X-User-Context-Hash', 300],
            ['X-User-Context', 3600],
        ];
    }
}

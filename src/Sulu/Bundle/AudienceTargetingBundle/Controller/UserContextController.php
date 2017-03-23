<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Controller;

use FOS\HttpCache\UserContext\HashGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller responsible for creating a user context hash based on the audience targeting groups of the logged in user,
 * which is recognized by a cookie.
 */
class UserContextController
{
    /**
     * @var HashGenerator
     */
    private $hashGenerator;

    /**
     * @var string
     */
    private $hashHeader;

    /**
     * @var int
     */
    private $cacheLifeTime;

    public function __construct(HashGenerator $hashGenerator, $hashHeader, $cacheLifeTime)
    {
        $this->hashGenerator = $hashGenerator;
        $this->hashHeader = $hashHeader;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    /**
     * Takes the request and calculates a user context hash based on the user.
     */
    public function hashAction()
    {
        $response = new Response(null, 200, [
            $this->hashHeader => $this->hashGenerator->generateHash(),
            'Content-Type' => 'application/vnd.fos.user-context-hash',
        ]);

        $response->setVary('cookie');
        $response->setSharedMaxAge($this->cacheLifeTime);

        return $response;
    }
}

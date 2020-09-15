<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Application;

use Symfony\Component\HttpFoundation\Response;

class TestController
{
    public function index()
    {
        return new Response(null, 200);
    }
}

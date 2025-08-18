<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SuluTestCase extends WebTestCase
{
    use KernelTrait;
    use AssertHttpStatusCodeTrait;
    use CreateClientTrait;
    use PurgeDatabaseTrait;
    use TestUserTrait;
}

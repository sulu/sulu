<?php

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DataFixtures\fixtures;

use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;

class FoobarFixture implements DocumentFixtureInterface
{
    public function load(DocumentManager $documentManager)
    {
    }

    public function getOrder()
    {
        return 10;
    }
}

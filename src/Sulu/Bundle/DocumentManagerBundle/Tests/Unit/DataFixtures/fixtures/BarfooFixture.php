<?php

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DataFixtures\fixtures;

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Component\DocumentManager\DocumentManager;

class BarfooFixture implements DocumentFixtureInterface
{
    public function load(DocumentManager $documentManager)
    {
    }

    public function getOrder()
    {
        return 20;
    }
}

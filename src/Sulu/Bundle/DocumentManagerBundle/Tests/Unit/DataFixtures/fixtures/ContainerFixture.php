<?php

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DataFixtures\fixtures;

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerFixture implements DocumentFixtureInterface, ContainerAwareInterface
{
    public $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(DocumentManager $documentManager)
    {
    }

    public function getOrder()
    {
        return 30;
    }
}

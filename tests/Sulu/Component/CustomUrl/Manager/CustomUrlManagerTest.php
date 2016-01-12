<?php

namespace Sulu\Component\CustomUrl\Manager;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class CustomUrlManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $sessionManager = $this->prophesize(SessionManagerInterface::class);

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $sessionManager->reveal()
        );

        $document = $manager->create(['title' => 'Test']);

        $document->persist($document)->shouldBeCalledTimes(1);

        $this->assertEquals('Test', $document->getTitle());
    }

    public function testReadList()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $customUrlRepository = $this->prophesize(CustomUrlRepository::class);
        $sessionManager = $this->prophesize(SessionManagerInterface::class);

        $manager = new CustomUrlManager(
            $documentManager->reveal(),
            $customUrlRepository->reveal(),
            $sessionManager->reveal()
        );

        $customUrlRepository->findList('/cmf/sulu_io/custom-urls/items')->willReturn(
            [['title' => 'Test-1'], ['title' => 'Test-2']]
        );

        $result = $manager->readList('sulu_io');

        $this->assertEquals([['title' => 'Test-1'], ['title' => 'Test-2']], $result);
    }
}

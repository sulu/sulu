<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Tests\Unit\Application\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\Messages\CreateCustomUrlMessage;
use Sulu\CustomUrl\Application\Messages\RemoveCustomUrlMessage;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Uid\Uuid;

class RemoveCustomUrlMessageHandlerTest extends KernelTestCase
{
    private RemoveCustomUrlMessageHandler $handler;
    private CustomUrlRepositoryInterface $customUrlRepository;
    private CustomUrlRouteRepositoryInterface $customUrlRouteRepository;
    private EntityManagerInterface $entityManager;

    private string $idOfObjectToModify;
    private string $targetDocument;

    public function setup(): void
    {
        self::bootKernel();
        $container = $this->getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->handler = $container->get(RemoveCustomUrlMessageHandler::class);
        $this->customUrlRouteRepository = $container->get(CustomUrlRouteRepositoryInterface::class);

        $this->customUrlRepository = $container->get('sulu_custom_urls.repository');
        // Delete all custom URLs to clear the db
        $this->customUrlRepository->createQueryBuilder('t')->delete()->getQuery()->execute();

        $this->targetDocument = Uuid::v4()->toRfc4122();

        $createdObject = $container->get(MessageBusInterface::class)
            ->dispatch(new CreateCustomUrlMessage(
                'sulu_io',
                [
                    'title' => 'Some title',
                    'published' => false,
                    'baseDomain' => 'localhost/*',
                    'domainParts' => ['test'],
                    'targetDocument' => $this->targetDocument,
                    'targetLocale' => 'en',
                    'canonical' => true,
                    'redirect' => false,
                    'noFollow' => true,
                    'noIndex' => true,
                ]
            ))->all(HandledStamp::class)[0]->getResult();
        $this->assertInstanceOf(CustomUrlInterface::class, $createdObject, 'Could not create custom url');

        $this->idOfObjectToModify = $createdObject->getId();

        // Flushing is handled outside by a stamp
        $this->entityManager->flush();
    }

    public function testCreateCustomUrlMessageHandler(): void
    {
        $this->handler->__invoke(new RemoveCustomUrlMessage(
            uuid: $this->idOfObjectToModify,
            webspaceKey: 'sulu_io',
        ));

        $this->entityManager->flush();

        // Checking that the custom Url was created
        $customUrls = $this->customUrlRepository->findAll();
        $this->assertCount(0, $customUrls);

        // Checking that the history was created
        $routeCount = $this->customUrlRouteRepository->count();
        $this->assertSame(0, $routeCount);
    }
}

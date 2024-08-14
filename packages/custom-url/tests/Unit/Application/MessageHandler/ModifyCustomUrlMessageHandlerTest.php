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
use Sulu\CustomUrl\Application\MessageHandler\ModifyCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\Messages\CreateCustomUrlMessage;
use Sulu\CustomUrl\Application\Messages\ModifyCustomUrlMessage;
use Sulu\CustomUrl\Domain\Exception\MismatchingDomainPartException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Uid\Uuid;

class ModifyCustomUrlMessageHandlerTest extends KernelTestCase
{
    private ModifyCustomUrlMessageHandler $handler;
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
        $this->handler = $container->get(ModifyCustomUrlMessageHandler::class);
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

    public function testModifyCustomUrl(): void
    {
        $createdObject = $this->handler->__invoke(new ModifyCustomUrlMessage(
            uuid: $this->idOfObjectToModify,
            webspaceKey: 'sulu_io',
            data: [
                'published' => true,
                'baseDomain' => 'localhost/*/*',
                'domainParts' => ['1', '2'],
            ]
        ));

        $this->entityManager->flush();

        // Checking that the custom Url was modified
        $customUrl = $this->customUrlRepository->findAll()[0] ?? null;
        $this->assertInstanceOf(CustomUrlInterface::class, $customUrl);

        $this->assertTrue($customUrl->isPublished());
        $this->assertSame('localhost/*/*', $customUrl->getBaseDomain());
        $this->assertSame(['1', '2'], $customUrl->getDomainParts());

        // Checking that the history was modified
        $routes = $this->customUrlRouteRepository->findByCustomUrl($customUrl);
        $this->assertCount(2, $routes);
        $this->assertSame('localhost/test', $routes[0]->getPath());
        $this->assertSame('localhost/1/2', $routes[1]->getPath());
    }

    public function testModifyCustomUrlWithTooManyPlaceholders(): void
    {
        $this->expectException(MismatchingDomainPartException::class);
        $this->expectExceptionMessage('Domain-part mismatch "localhost/*/*/*" with placeholders: 1, 2');

        $this->handler->__invoke(new ModifyCustomUrlMessage(
            uuid: $this->idOfObjectToModify,
            webspaceKey: 'sulu_io',
            data: [
                'published' => true,
                'baseDomain' => 'localhost/*/*/*',
                'domainParts' => ['1', '2'],
            ]
        ));
    }

    public function testModifyCustomUrlWithTooManyDomainParts(): void
    {
        $this->expectException(MismatchingDomainPartException::class);
        $this->expectExceptionMessage('Domain-part mismatch "localhost/*/*" with placeholders: 1, 2, 3');

        $this->handler->__invoke(new ModifyCustomUrlMessage(
            uuid: $this->idOfObjectToModify,
            webspaceKey: 'sulu_io',
            data: [
                'published' => true,
                'baseDomain' => 'localhost/*/*',
                'domainParts' => ['1', '2', '3'],
            ]
        ));
    }

    public function testModifyCustomWithUrlGeneration(): void
    {
        $this->handler->__invoke(new ModifyCustomUrlMessage(
            uuid: $this->idOfObjectToModify,
            webspaceKey: 'sulu_io',
            data: [
                'published' => true,
            ]
        ));
        $this->entityManager->flush();

        // Checking that the custom Url was created
        $customUrl = $this->customUrlRepository->findAll()[0] ?? null;
        $this->assertInstanceOf(CustomUrlInterface::class, $customUrl);

        $this->assertTrue($customUrl->isPublished());
        $this->assertSame('localhost/*', $customUrl->getBaseDomain());
        $this->assertSame(['test'], $customUrl->getDomainParts());

        // Checking that the history was created
        $routes = $this->customUrlRouteRepository->findByCustomUrl($customUrl);
        $this->assertCount(1, $routes);
        $this->assertSame('localhost/test', $routes[0]->getPath());
    }

    public function testModifyCustomWithDifferentUrl(): void
    {
        $this->handler->__invoke(new ModifyCustomUrlMessage(
            uuid: $this->idOfObjectToModify,
            webspaceKey: 'sulu_io',
            data: [
                'published' => true,
                'baseDomain' => 'localhost/*',
                'domainParts' => ['some-other'],
            ]
        ));
        $this->entityManager->flush();

        $customUrl = $this->customUrlRepository->findAll()[0];
        $this->assertTrue($customUrl->isPublished());
        $this->assertSame('localhost/*', $customUrl->getBaseDomain());
        $this->assertSame(['some-other'], $customUrl->getDomainParts());

        // Checking that the history was created
        $routes = $this->customUrlRouteRepository->findByCustomUrl($customUrl);
        $this->assertCount(2, $routes);
        $this->assertSame('localhost/test', $routes[0]->getPath());
        $this->assertSame('localhost/some-other', $routes[1]->getPath());
    }
}

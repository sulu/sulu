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
use Sulu\CustomUrl\Application\MessageHandler\CreateCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\Messages\CreateCustomUrlMessage;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class CreateCustomUrlMessageHandlerTest extends KernelTestCase
{
    private CreateCustomUrlMessageHandler $handler;
    private CustomUrlRepositoryInterface $customUrlRepository;
    private EntityManagerInterface $entityManager;

    public function setup(): void
    {
        self::bootKernel();
        $container = $this->getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->handler = $container->get(CreateCustomUrlMessageHandler::class);

        $this->customUrlRepository = $container->get('sulu_custom_urls.repository');
        // Delete all custom URLs to clear the db
        foreach ($this->customUrlRepository->findBy() as $customUrl) {
            $this->customUrlRepository->remove($customUrl);
        } $this->entityManager->flush();
    }

    public function testCreateCustomUrl(): void
    {
        $targetDocument = Uuid::v4()->toRfc4122();
        $this->handler->__invoke(new CreateCustomUrlMessage(
            'sulu_io',
            [
                'title' => 'Some title',
                'published' => false,
                'baseDomain' => 'localhost/*',
                'domainParts' => ['test'],
                'targetDocument' => $targetDocument,
                'targetLocale' => 'en',
                'canonical' => true,
                'redirect' => false,
                'noFollow' => true,
                'noIndex' => true,
            ],
        ));

        // Flushing is handled outside by a stamp
        $this->entityManager->flush();

        // Checking that the custom Url was created
        $customUrl = \iterator_to_array($this->customUrlRepository->findBy())[0];
        $this->assertSame('Some title', $customUrl->getTitle());
        $this->assertSame('sulu_io', $customUrl->getWebspace());
        $this->assertFalse($customUrl->isPublished());
        $this->assertSame('localhost/*', $customUrl->getBaseDomain());
        $this->assertSame(['test'], $customUrl->getDomainParts());
        $this->assertSame($targetDocument, $customUrl->getTargetDocument());
        $this->assertSame('en', $customUrl->getTargetLocale());
        $this->assertTrue($customUrl->isCanonical());
        $this->assertFalse($customUrl->isRedirect());
        $this->assertTrue($customUrl->isNoFollow());
        $this->assertTrue($customUrl->isNoIndex());

        // Checking that the history was created
        $routes = \iterator_to_array($customUrl->getRoutes());
        $this->assertCount(1, $routes);
        $this->assertSame('localhost/test', $routes[0]->getPath());
    }
}

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

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Application\MessageHandler\CreateCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\Messages\CreateCustomUrlMessage;
use Sulu\CustomUrl\Domain\Event\CustomUrlCreatedEvent;
use Sulu\CustomUrl\Domain\Exception\CustomUrlAlreadyExistsException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\Uid\Uuid;

class CreateCustomUrlMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CustomUrlRepositoryInterface> */
    private ObjectProphecy $customUrlRepository;

    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;

    /** @var ObjectProphecy<CustomUrlMapperInterface> */
    private ObjectProphecy $customUrlMapper;

    private CreateCustomUrlMessageHandler $handler;

    protected function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->customUrlMapper = $this->prophesize(CustomUrlMapperInterface::class);

        $this->handler = new CreateCustomUrlMessageHandler(
            [$this->customUrlMapper->reveal()],
            $this->customUrlRepository->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testCreateCustomUrl(): void
    {
        $targetDocument = Uuid::v4()->toRfc4122();
        $data = [
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
        ];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getTitle()->willReturn('Some title');

        // Setup repository expectations
        $this->customUrlRepository->createNew(null)
            ->willReturn($customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->customUrlRepository->findOneBy(['title' => 'Some title'])
            ->shouldBeCalledOnce()
            ->willReturn(null); // No existing custom URL with same title

        $this->customUrlRepository->add($customUrl->reveal())
            ->shouldBeCalledOnce();

        // Setup mapper expectations
        $this->customUrlMapper->mapCustomUrlData($customUrl->reveal(), $data)
            ->shouldBeCalledOnce();

        // Setup entity expectations
        $customUrl->setWebspace('sulu_io')
            ->shouldBeCalledOnce();

        // Setup event collector expectations
        $this->domainEventCollector->collect(Argument::that(function($event) use ($customUrl, $data) {
            return $event instanceof CustomUrlCreatedEvent
                && $event->getCustomUrl() === $customUrl->reveal()
                && $event->getEventPayload() === $data;
        }))->shouldBeCalledOnce();

        $message = new CreateCustomUrlMessage('sulu_io', $data);

        $result = $this->handler->__invoke($message);

        $this->assertSame($customUrl->reveal(), $result);
    }

    public function testCreateCustomUrlWithExistingTitle(): void
    {
        $targetDocument = Uuid::v4()->toRfc4122();
        $data = [
            'title' => 'Duplicate title',
            'published' => false,
            'baseDomain' => 'localhost/*',
            'domainParts' => ['test'],
            'targetDocument' => $targetDocument,
            'targetLocale' => 'en',
            'canonical' => true,
            'redirect' => false,
            'noFollow' => false,
            'noIndex' => false,
        ];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getTitle()->willReturn('Duplicate title');

        $existingCustomUrl = $this->prophesize(CustomUrlInterface::class);

        $this->customUrlRepository->createNew(null)
            ->willReturn($customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->customUrlRepository->findOneBy(['title' => 'Duplicate title'])
            ->shouldBeCalledOnce()
            ->willReturn($existingCustomUrl->reveal()); // Existing custom URL found

        $this->customUrlMapper->mapCustomUrlData($customUrl->reveal(), $data)
            ->shouldBeCalledOnce();

        $customUrl->setWebspace('sulu_io')
            ->shouldBeCalledOnce();

        // Should NOT call add or collect event
        $this->customUrlRepository->add(Argument::any())
            ->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::any())
            ->shouldNotBeCalled();

        $this->expectException(CustomUrlAlreadyExistsException::class);
        $this->expectExceptionMessage('Duplicate title');

        $message = new CreateCustomUrlMessage('sulu_io', $data);

        $this->handler->__invoke($message);
    }

    public function testCreateCustomUrlWithCustomUuid(): void
    {
        $customUuid = Uuid::v4()->toRfc4122();
        $targetDocument = Uuid::v4()->toRfc4122();
        $data = [
            'uuid' => $customUuid, // UUID goes in data array
            'title' => 'Custom UUID title',
            'published' => true,
            'baseDomain' => 'example.com/*',
            'domainParts' => ['custom'],
            'targetDocument' => $targetDocument,
            'targetLocale' => 'de',
            'canonical' => false,
            'redirect' => true,
            'noFollow' => false,
            'noIndex' => false,
        ];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getTitle()->willReturn('Custom UUID title');

        $this->customUrlRepository->createNew($customUuid)
            ->willReturn($customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->customUrlRepository->findOneBy(['title' => 'Custom UUID title'])
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->customUrlRepository->add($customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->customUrlMapper->mapCustomUrlData($customUrl->reveal(), $data)
            ->shouldBeCalledOnce();

        $customUrl->setWebspace('sulu_io')
            ->shouldBeCalledOnce();

        $this->domainEventCollector->collect(Argument::type(CustomUrlCreatedEvent::class))
            ->shouldBeCalledOnce();

        $message = new CreateCustomUrlMessage('sulu_io', $data);

        $result = $this->handler->__invoke($message);

        $this->assertSame($customUrl->reveal(), $result);
    }
}

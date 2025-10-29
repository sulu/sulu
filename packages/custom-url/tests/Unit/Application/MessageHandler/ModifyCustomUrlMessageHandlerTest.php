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
use Sulu\CustomUrl\Application\MessageHandler\ModifyCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\Messages\ModifyCustomUrlMessage;
use Sulu\CustomUrl\Domain\Event\CustomUrlModifiedEvent;
use Sulu\CustomUrl\Domain\Exception\CustomUrlAlreadyExistsException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

class ModifyCustomUrlMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CustomUrlRepositoryInterface> */
    private ObjectProphecy $customUrlRepository;

    /** @var ObjectProphecy<DomainEventCollectorInterface> */
    private ObjectProphecy $domainEventCollector;

    /** @var ObjectProphecy<CustomUrlMapperInterface> */
    private ObjectProphecy $customUrlMapper;

    private ModifyCustomUrlMessageHandler $handler;

    protected function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->customUrlMapper = $this->prophesize(CustomUrlMapperInterface::class);

        $this->handler = new ModifyCustomUrlMessageHandler(
            [$this->customUrlMapper->reveal()],
            $this->customUrlRepository->reveal(),
            $this->domainEventCollector->reveal()
        );
    }

    public function testModifyCustomUrl(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $data = [
            'published' => true,
            'baseDomain' => 'localhost/*/*',
            'domainParts' => ['1', '2'],
        ];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');
        $customUrl->getTitle()->willReturn('Some title');
        $customUrl->getUuid()->willReturn($uuid);

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        $this->customUrlMapper->mapCustomUrlData($customUrl->reveal(), $data)
            ->shouldBeCalledOnce();

        $this->customUrlRepository->findOneBy(['title' => 'Some title'])
            ->shouldBeCalledOnce()
            ->willReturn(null); // No other custom URL with same title

        $this->customUrlRepository->add($customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->domainEventCollector->collect(Argument::that(function($event) use ($customUrl, $data) {
            return $event instanceof CustomUrlModifiedEvent
                && $event->getCustomUrl() === $customUrl->reveal()
                && $event->getEventPayload() === $data;
        }))->shouldBeCalledOnce();

        $message = new ModifyCustomUrlMessage($uuid, 'sulu_io', $data);

        $result = $this->handler->__invoke($message);

        $this->assertSame($customUrl->reveal(), $result);
    }

    public function testModifyCustomUrlWithWrongWebspace(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $data = ['published' => true];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        // Should NOT call mapper, repository add, or event collector
        $this->customUrlMapper->mapCustomUrlData(Argument::any(), Argument::any())
            ->shouldNotBeCalled();
        $this->customUrlRepository->add(Argument::any())
            ->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::any())
            ->shouldNotBeCalled();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Entity from webspace "sulu_io" does not belong to webspace "wrong_webspace"');

        $message = new ModifyCustomUrlMessage($uuid, 'wrong_webspace', $data);

        $this->handler->__invoke($message);
    }

    public function testModifyCustomUrlWithDuplicateTitle(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $otherUuid = Uuid::v4()->toRfc4122();
        $data = ['title' => 'Duplicate title'];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');
        $customUrl->getTitle()->willReturn('Duplicate title');
        $customUrl->getUuid()->willReturn($uuid);

        $existingCustomUrl = $this->prophesize(CustomUrlInterface::class);
        $existingCustomUrl->getUuid()->willReturn($otherUuid);

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        $this->customUrlMapper->mapCustomUrlData($customUrl->reveal(), $data)
            ->shouldBeCalledOnce();

        $this->customUrlRepository->findOneBy(['title' => 'Duplicate title'])
            ->shouldBeCalledOnce()
            ->willReturn($existingCustomUrl->reveal()); // Another custom URL with same title exists

        // Should NOT call add or collect event
        $this->customUrlRepository->add(Argument::any())
            ->shouldNotBeCalled();
        $this->domainEventCollector->collect(Argument::any())
            ->shouldNotBeCalled();

        $this->expectException(CustomUrlAlreadyExistsException::class);
        $this->expectExceptionMessage('Duplicate title');

        $message = new ModifyCustomUrlMessage($uuid, 'sulu_io', $data);

        $this->handler->__invoke($message);
    }

    public function testModifyCustomUrlWithSameTitleAsItself(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $data = ['title' => 'Same title'];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');
        $customUrl->getTitle()->willReturn('Same title');
        $customUrl->getUuid()->willReturn($uuid);

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        $this->customUrlMapper->mapCustomUrlData($customUrl->reveal(), $data)
            ->shouldBeCalledOnce();

        // Same custom URL returned - should be allowed
        $this->customUrlRepository->findOneBy(['title' => 'Same title'])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        $this->customUrlRepository->add($customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->domainEventCollector->collect(Argument::type(CustomUrlModifiedEvent::class))
            ->shouldBeCalledOnce();

        $message = new ModifyCustomUrlMessage($uuid, 'sulu_io', $data);

        $result = $this->handler->__invoke($message);

        $this->assertSame($customUrl->reveal(), $result);
    }

    public function testModifyCustomUrlWithMinimalData(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $data = ['published' => true];

        $customUrl = $this->prophesize(CustomUrlInterface::class);
        $customUrl->getWebspace()->willReturn('sulu_io');
        $customUrl->getTitle()->willReturn('Original title');
        $customUrl->getUuid()->willReturn($uuid);

        $this->customUrlRepository->getOneBy(['uuid' => $uuid])
            ->shouldBeCalledOnce()
            ->willReturn($customUrl->reveal());

        $this->customUrlMapper->mapCustomUrlData($customUrl->reveal(), $data)
            ->shouldBeCalledOnce();

        $this->customUrlRepository->findOneBy(['title' => 'Original title'])
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $this->customUrlRepository->add($customUrl->reveal())
            ->shouldBeCalledOnce();

        $this->domainEventCollector->collect(Argument::type(CustomUrlModifiedEvent::class))
            ->shouldBeCalledOnce();

        $message = new ModifyCustomUrlMessage($uuid, 'sulu_io', $data);

        $result = $this->handler->__invoke($message);

        $this->assertSame($customUrl->reveal(), $result);
    }
}

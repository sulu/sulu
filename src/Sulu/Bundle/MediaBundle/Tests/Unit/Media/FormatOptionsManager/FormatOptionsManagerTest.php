<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatOptionsManager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaCropModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaCropRemovedEvent;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManager;
use Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

class FormatOptionsManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $em;

    /**
     * @var ObjectProphecy<EntityRepository>
     */
    private $formatOptionsRepository;

    /**
     * @var ObjectProphecy<MediaManagerInterface>
     */
    private $mediaManager;

    /**
     * @var ObjectProphecy<FormatManagerInterface>
     */
    private $formatManager;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    /**
     * @var MediaInterface[]
     */
    private $media;

    /**
     * @var FormatOptions[]
     */
    private $formatOptions;

    /**
     * @var FormatOptionsManagerInterface
     */
    private $formatOptionsManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->formatOptionsRepository = $this->prophesize(EntityRepository::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->formatManager = $this->prophesize(FormatManagerInterface::class);
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);

        $this->formatOptionsManager = new FormatOptionsManager(
            $this->em->reveal(),
            $this->formatOptionsRepository->reveal(),
            $this->mediaManager->reveal(),
            $this->formatManager->reveal(),
            $this->domainEventCollector->reveal(),
            [
                'sulu-50x50' => [],
                'sulu-100x100' => [],
            ]
        );

        $this->setUpMedia();
        $this->setUpFormatOptions();
    }

    private function setUpMedia(): void
    {
        $this->media[] = new Media();

        $file = new File();
        $file->setVersion(1);
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $file->addFileVersion($fileVersion);

        $this->media[0]->addFile($file);
    }

    private function setUpFormatOptions(): void
    {
        $this->formatOptions[] = new FormatOptions();
        $this->formatOptions[0]->setFormatKey('sulu-50x50');
        $this->formatOptions[0]->setCropX(2);
        $this->formatOptions[0]->setCropY(3);
        $this->formatOptions[0]->setCropHeight(5);
        $this->formatOptions[0]->setCropWidth(7);

        $this->formatOptions[] = new FormatOptions();
        $this->formatOptions[1]->setFormatKey('sulu-100x100');
        $this->formatOptions[1]->setCropX(11);
        $this->formatOptions[1]->setCropY(13);
        $this->formatOptions[1]->setCropHeight(17);
        $this->formatOptions[1]->setCropWidth(19);

        $this->media[0]->getFiles()[0]->getFileVersions()[0]->addFormatOptions($this->formatOptions[1]);
    }

    public function testGet(): void
    {
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);
        $this->formatOptionsRepository->find(
            [
                'fileVersion' => $this->media[0]->getFiles()[0]->getFileVersions()[0],
                'formatKey' => 'sulu-50x50',
            ]
        )->willReturn($this->formatOptions[0]);

        $formatOptions = $this->formatOptionsManager->get(42, 'sulu-50x50');

        $this->assertEquals(2, $formatOptions['cropX']);
        $this->assertEquals(3, $formatOptions['cropY']);
        $this->assertEquals(5, $formatOptions['cropHeight']);
        $this->assertEquals(7, $formatOptions['cropWidth']);
    }

    public function testGetNotExistingFormat(): void
    {
        $this->expectException(FormatNotFoundException::class);
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);
        $this->formatOptionsRepository->find(
            [
                'fileVersion' => $this->media[0]->getFiles()[0]->getFileVersions()[0],
                'formatKey' => 'sulu-50x50',
            ]
        )->willReturn($this->formatOptions[0]);

        $this->formatOptionsManager->get(42, 'not-existing');
    }

    public function testGetAll(): void
    {
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);
        $this->formatOptionsRepository->findBy(
            [
                'fileVersion' => $this->media[0]->getFiles()[0]->getFileVersions()[0],
            ]
        )->willReturn($this->formatOptions);

        $formatOptions = $this->formatOptionsManager->getAll(42);

        $this->assertEquals(2, $formatOptions['sulu-50x50']['cropX']);
        $this->assertEquals(3, $formatOptions['sulu-50x50']['cropY']);
        $this->assertEquals(5, $formatOptions['sulu-50x50']['cropHeight']);
        $this->assertEquals(7, $formatOptions['sulu-50x50']['cropWidth']);

        $this->assertEquals(11, $formatOptions['sulu-100x100']['cropX']);
        $this->assertEquals(13, $formatOptions['sulu-100x100']['cropY']);
        $this->assertEquals(17, $formatOptions['sulu-100x100']['cropHeight']);
        $this->assertEquals(19, $formatOptions['sulu-100x100']['cropWidth']);
    }

    public function testGetAllNotExistingFormat(): void
    {
        $this->expectException(FormatNotFoundException::class);
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);
        $this->formatOptionsRepository->findBy(
            [
                'fileVersion' => $this->media[0]->getFiles()[0]->getFileVersions()[0],
            ]
        )->willReturn($this->formatOptions);

        $this->formatOptionsManager->get(42, 'not-existing');
    }

    public function testSave(): void
    {
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);

        $formatOptions = $this->formatOptionsManager->save(
            42,
            'sulu-100x100',
            [
                'cropX' => 10.05,
                'cropY' => 11.15,
                'cropHeight' => 12.12,
                'cropWidth' => 13.42,
            ]
        );

        $this->em->persist(Argument::type(FormatOptions::class))->shouldHaveBeenCalled();
        $this->domainEventCollector->collect(Argument::type(MediaCropModifiedEvent::class))->shouldHaveBeenCalled();
        $this->formatManager->purge(42, Argument::any(), Argument::any(), Argument::any())->shouldHaveBeenCalled();

        $this->assertEquals(10, $formatOptions->getCropX());
        $this->assertEquals(11, $formatOptions->getCropY());
        $this->assertEquals(12, $formatOptions->getCropHeight());
        $this->assertEquals(13, $formatOptions->getCropWidth());
    }

    public function testSaveNotExisting(): void
    {
        $this->expectException(FormatNotFoundException::class);
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);

        $this->formatOptionsManager->save(
            42,
            'not-existing',
            [
                'cropX' => 10,
                'cropY' => 11,
                'cropHeight' => 12,
                'cropWidth' => 13,
            ]
        );

        $this->em->persist(Argument::type(FormatOptions::class))->shouldNotHaveBeenCalled();
        $this->domainEventCollector->collect(Argument::type(MediaCropModifiedEvent::class))->shouldNotHaveBeenCalled();
        $this->formatManager->purge(42, Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    public function testDelete(): void
    {
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);

        $this->formatOptionsManager->delete(42, 'sulu-100x100');

        $this->em->remove(Argument::type(FormatOptions::class))->shouldHaveBeenCalled();
        $this->domainEventCollector->collect(Argument::type(MediaCropRemovedEvent::class))->shouldHaveBeenCalled();
        $this->formatManager->purge(42, Argument::any(), Argument::any(), Argument::any())->shouldHaveBeenCalled();
    }

    public function testDeleteNotExisting(): void
    {
        $this->mediaManager->getEntityById(42)->willReturn($this->media[0]);

        $this->formatOptionsManager->delete(42, 'sulu-50x50');

        $this->em->remove(Argument::type(FormatOptions::class))->shouldNotHaveBeenCalled();
        $this->domainEventCollector->collect(Argument::type(MediaCropRemovedEvent::class))->shouldNotHaveBeenCalled();
        $this->formatManager->purge(42, Argument::any(), Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }
}

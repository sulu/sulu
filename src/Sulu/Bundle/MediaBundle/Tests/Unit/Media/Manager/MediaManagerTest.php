<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Doctrine\ORM\EntityManager;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Video\VideoUtilsInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MediaManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var ObjectProphecy
     */
    private $mediaRepository;

    /**
     * @var ObjectProphecy
     */
    private $collectionRepository;

    /**
     * @var ObjectProphecy
     */
    private $userRepository;

    /**
     * @var ObjectProphecy
     */
    private $em;

    /**
     * @var ObjectProphecy
     */
    private $storage;

    /**
     * @var ObjectProphecy
     */
    private $validator;

    /**
     * @var ObjectProphecy
     */
    private $formatManager;

    /**
     * @var ObjectProphecy
     */
    private $tagManager;

    /**
     * @var ObjectProphecy
     */
    private $typeManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var VideoUtilsInterface
     */
    private $videoUtils;

    public function setUp()
    {
        parent::setUp();

        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);
        $this->collectionRepository = $this->prophesize(CollectionRepositoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->em = $this->prophesize(EntityManager::class);
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->validator = $this->prophesize(FileValidatorInterface::class);
        $this->formatManager = $this->prophesize(FormatManagerInterface::class);
        $this->tagManager = $this->prophesize(TagManagerInterface::class);
        $this->typeManager = $this->prophesize(TypeManagerInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->videoUtils = $this->prophesize(VideoUtilsInterface::class);

        $this->mediaManager = new MediaManager(
            $this->mediaRepository->reveal(),
            $this->collectionRepository->reveal(),
            $this->userRepository->reveal(),
            $this->em->reveal(),
            $this->storage->reveal(),
            $this->validator->reveal(),
            $this->formatManager->reveal(),
            $this->tagManager->reveal(),
            $this->typeManager->reveal(),
            $this->tokenStorage->reveal(),
            $this->securityChecker->reveal(),
            $this->videoUtils->reveal(),
            [
                'view' => 64,
            ],
            '/',
            0
        );
    }

    /**
     * @dataProvider provideGetByIds
     */
    public function testGetByIds($ids, $media, $result)
    {
        $this->mediaRepository->findMedia(Argument::any())->willReturn($media);
        $this->formatManager->getFormats(Argument::cetera())->willReturn(null);
        $medias = $this->mediaManager->getByIds($ids, 'en');

        for ($i = 0; $i < count($medias); ++$i) {
            $this->assertEquals($result[$i]->getId(), $medias[$i]->getId());
        }
    }

    public function testGetWithoutToken()
    {
        $this->tokenStorage->getToken()->willReturn(null);
        $this->mediaRepository->findMedia(Argument::cetera())->willReturn([])->shouldBeCalled();
        $this->mediaRepository->count(Argument::cetera())->shouldBeCalled();

        $this->mediaManager->get(1);
    }

    public function testDeleteWithSecurity()
    {
        $collection = $this->prophesize(Collection::class);
        $collection->getId()->willReturn(2);
        $media = $this->prophesize(Media::class);
        $media->getCollection()->willReturn($collection);
        $media->getFiles()->willReturn([]);

        $this->mediaRepository->findMediaById(1)->willReturn($media);
        $this->securityChecker->checkPermission(
            new SecurityCondition('sulu.media.collections', null, Collection::class, 2),
            'delete'
        )->shouldBeCalled();

        $this->mediaManager->delete(1, true);
    }

    public function provideGetByIds()
    {
        $media1 = $this->createMedia(1);
        $media2 = $this->createMedia(2);
        $media3 = $this->createMedia(3);

        return [
            [[1, 2, 3], [$media1, $media2, $media3], [$media1, $media2, $media3]],
            [[2, 1, 3], [$media1, $media2, $media3], [$media2, $media1, $media3]],
            [[4, 1, 2], [$media1, $media2], [$media1, $media2]],
        ];
    }

    protected function createMedia($id)
    {
        $mediaIdReflection = new \ReflectionProperty(Media::class, 'id');
        $mediaIdReflection->setAccessible(true);

        $media = new Media();
        $mediaIdReflection->setValue($media, $id);

        $file = new File();
        $fileVersion = new FileVersion();
        $fileVersion->setName('Media' . $id);
        $file->addFileVersion($fileVersion);
        $media->addFile($file);

        return $media;
    }
}

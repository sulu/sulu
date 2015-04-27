<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;

class DefaultMediaManagerTest extends \PHPUnit_Framework_TestCase
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

    public function setUp()
    {
        parent::setUp();

        $this->mediaRepository = $this->prophesize('Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface');
        $this->collectionRepository = $this->prophesize('Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface');
        $this->userRepository = $this->prophesize('Sulu\Component\Security\Authentication\UserRepositoryInterface');
        $this->em = $this->prophesize('Doctrine\ORM\EntityManager');
        $this->storage = $this->prophesize('Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface');
        $this->validator = $this->prophesize('Sulu\Bundle\MediaBundle\Media\FileValidator\FileValidatorInterface');
        $this->formatManager = $this->prophesize('Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface');
        $this->tagManager = $this->prophesize('Sulu\Bundle\TagBundle\Tag\TagManagerInterface');
        $this->typeManager = $this->prophesize('Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface');

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

        for ($i = 0; $i < count($medias); $i++) {
            $this->assertEquals($result[$i]->getId(), $medias[$i]->getId());
        }
    }

    public function provideGetByIds()
    {
        $media1 = $this->createMedia(1);
        $media2 = $this->createMedia(2);
        $media3 = $this->createMedia(3);

        return array(
            array(array(1, 2, 3), array($media1, $media2, $media3), array($media1, $media2, $media3)),
            array(array(2, 1, 3), array($media1, $media2, $media3), array($media2, $media1, $media3)),
            array(array(4, 1, 2), array($media1, $media2), array($media1, $media2)),
        );
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

<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\TypeManager;

use Doctrine\Common\Persistence\ObjectManager;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TypeManagerTest extends SuluTestCase
{
    /**
     * @var TypeManager
     */
    private $typeManager;

    /**
     * @var ObjectProphecy
     */
    private $em;

    private $mediaTypes = array(
        array(
            'type' => 'document',
            'mimeTypes' => array('*')
        ),
        array(
            'type' => 'image',
            'mimeTypes' => array('image/*')
        ),
        array(
            'type' => 'video',
            'mimeTypes' => array('video/*')
        ),
        array(
            'type' => 'audio',
            'mimeTypes' => array('audio/*')
        )
    );

    public function setUp()
    {
        parent::setUp();

        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        foreach ($this->mediaTypes as $mediaTypeData) {
            $mediaType = new MediaType();
            $mediaType->setName($mediaTypeData['type']);
            $em->persist($mediaType);
        }

        $em->flush();

        $this->typeManager = new TypeManager(
            $em,
            $this->mediaTypes,
            array('file/exe')
        );
    }

    public function testMediaTypes()
    {
        $data = array(
            // documents
            'application/pdf' => 'document',
            'application/msword' => 'document',
            'application/vnd.ms-excel' => 'document',
            'application/zip' => 'document',
            'text/html' => 'document',
            // images
            'image/jpg' => 'image',
            'image/jepg' => 'image',
            'image/gif' => 'image',
            'image/png' => 'image',
            'image/vnd.adobe.photoshop' => 'image',
            // videos
            'video/mp4' => 'video',
            'video/mov' => 'video',
            // audios
            'audio/mpeg' => 'audio',
            'audio/mp3' => 'audio',
        );

        foreach ($data as $mimeType => $mediaType) {
            $mediaTypeName = null;
            $id = $this->typeManager->getMediaType($mimeType);
            $setMediaType = $this->typeManager->get($id);
            if ($setMediaType) {
                $mediaTypeName = $setMediaType->getName();
            }
            $this->assertEquals($mediaTypeName, $mediaType);
        }
    }
}

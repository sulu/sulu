<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class MediaDataProviderRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Tag[]
     */
    private $tags = [];

    /**
     * @var Media[]
     */
    private $medias = [];

    /**
     * @var array
     */
    private $tagData = [
        'Tag-0',
        'Tag-1',
        'Tag-2',
        'Tag-3',
    ];

    /**
     * @var array
     */
    private $mediaData = [
        ['Bild 1', 'image/jpg', 'image', [0, 1, 2]],
        ['Bild 2', 'image/jpg', 'image', [0, 1, 3]],
        ['Bild 3', 'image/png', 'image', [0, 1]],
        ['Bild 4', 'video/mov', 'video', [0, 1, 2]],
        ['Bild 5', 'video/mkv', 'video', [0]],
        ['Bild 6', 'application/pdf', 'document', [0]],
        ['Bild 7', 'application/pdf', 'document', [0]],
        ['Bild 8', 'application/pdf', 'document', []],
    ];

    /**
     * @var array
     */
    private $mediaTypeData = ['image', 'video', 'document'];

    /**
     * @var MediaType[]
     */
    private $mediaTypes = [];

    /**
     * @var Collection
     */
    private $collection;

    protected function setUp()
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->db('ORM')->getOm();

        $this->collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');

        $this->collection->setType($collectionType);
        $collectionMeta->setCollection($this->collection);
        $this->collection->addMeta($collectionMeta);

        $this->em->persist($this->collection);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionType);

        foreach ($this->mediaTypeData as $type) {
            $this->mediaTypes[$type] = $this->createType($type);
        }
        $this->em->flush();

        foreach ($this->tagData as $tag) {
            $this->tags[] = $this->createTag($tag);
        }
        $this->em->flush();

        foreach ($this->mediaData as $media) {
            $this->medias[] = $this->createMediaWithTags($media[0], $media[1], $media[2], $media[3]);
        }
        $this->em->flush();
    }

    private function createType($name)
    {
        $type = new MediaType();
        $type->setName($name);

        $this->em->persist($type);

        return $type;
    }

    private function createTag($name)
    {
        $tag = new Tag();
        $tag->setName($name);

        $this->em->persist($tag);

        return $tag;
    }

    private function createMediaWithTags($title, $mimeType, $type, $tags = [])
    {
        $media = new Media();
        $file = new File();
        $fileVersion = new FileVersion();
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setTitle($title);
        $fileVersionMeta->setLocale('de');
        $fileVersionMeta->setFileVersion($fileVersion);
        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setVersion(1);
        $fileVersion->setName($title);
        $fileVersion->setSize(0);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $file->setVersion(1);
        $file->addFileVersion($fileVersion);
        $file->setMedia($media);
        $media->addFile($file);
        $media->setType($this->mediaTypes[$type]);
        $media->setCollection($this->collection);

        foreach ($tags as $tag) {
            $fileVersion->addTag($this->tags[$tag]);
        }

        $this->em->persist($media);

        return $media;
    }

    public function findByProvider()
    {
        // when pagination is active the result count is pageSize + 1 to determine has next page

        return [
            // no pagination
            [[], null, 0, null, $this->mediaData],
            // page 1, no limit
            [[], 1, 3, null, array_slice($this->mediaData, 0, 4)],
            // page 2, no limit
            [[], 2, 3, null, array_slice($this->mediaData, 3, 4)],
            // page 3, no limit
            [[], 3, 3, null, array_slice($this->mediaData, 6, 2)],
            // no pagination, limit 3
            [[], null, 0, 3, array_slice($this->mediaData, 0, 3)],
            // page 1, limit 5
            [[], 1, 3, 5, array_slice($this->mediaData, 0, 4)],
            // page 2, limit 5
            [[], 2, 3, 5, array_slice($this->mediaData, 3, 2)],
            // page 3, limit 5
            [[], 3, 3, 5, []],
            // no pagination, tag 0
            [['tags' => [0], 'tagOperator' => 'or'], null, 0, null, array_slice($this->mediaData, 0, 7), [0]],
            // no pagination, tag 0 or 1
            [['tags' => [0, 1], 'tagOperator' => 'or'], null, 0, null, array_slice($this->mediaData, 0, 7)],
            // no pagination, tag 0 and 1
            [['tags' => [0, 1], 'tagOperator' => 'and'], null, 0, null, array_slice($this->mediaData, 0, 4), [0, 1]],
            // no pagination, tag 0 and 3
            [['tags' => [0, 3], 'tagOperator' => 'and'], null, 0, null, [$this->mediaData[1]], [0, 3]],
            // page 1, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                1,
                3,
                null,
                array_slice($this->mediaData, 0, 4),
                [0],
            ],
            // page 2, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                2,
                3,
                null,
                array_slice($this->mediaData, 3, 4),
                [0],
            ],
            // page 3, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                3,
                3,
                null,
                array_slice($this->mediaData, 6, 1),
                [0],
            ],
            // no pagination, website-tag 0
            [
                ['websiteTags' => [0], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->mediaData, 0, 7),
                [0],
            ],
            // no pagination, website-tag 0 or 1
            [
                ['websiteTags' => [0, 1], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->mediaData, 0, 7),
            ],
            // no pagination, website-tag 0 and 1
            [
                ['websiteTags' => [0, 1], 'websiteTagsOperator' => 'and'],
                null,
                0,
                null,
                array_slice($this->mediaData, 0, 4),
                [0, 1],
            ],
            // no pagination, website-tag 1, tags 3
            [
                ['websiteTags' => [1], 'websiteTagsOperator' => 'or', 'tags' => [3], 'tagOperator' => 'or'],
                null,
                0,
                null,
                [$this->mediaData[1]],
                [0, 3],
            ],
            // no pagination, website-tag 2 or 3, tags 1
            [
                ['websiteTags' => [2, 3], 'websiteTagsOperator' => 'or', 'tags' => [1], 'tagOperator' => 'or'],
                null,
                0,
                null,
                [$this->mediaData[0], $this->mediaData[1], $this->mediaData[3]],
                [0, 1],
            ],
            // no pagination, website-tag 1, tags 2 or 3
            [
                ['websiteTags' => [1], 'websiteTagsOperator' => 'or', 'tags' => [2, 3], 'tagOperator' => 'or'],
                null,
                0,
                null,
                [$this->mediaData[0], $this->mediaData[1], $this->mediaData[3]],
                [0, 1],
            ],
            // combination website/admin-tag
            [
                [
                    'tags' => [0],
                    'tagOperator' => 'or',
                    'websiteTags' => [1],
                    'websiteTagsOperator' => 'or',
                ],
                null,
                0,
                null,
                array_slice($this->mediaData, 0, 4),
                [0, 1],
            ],
            // options mimetype
            [
                [],
                null,
                0,
                null,
                array_slice($this->mediaData, 0, 2),
                [],
                ['mimetype' => 'image/jpg'],
            ],
            // options mimetype and admin tags
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->mediaData, 5, 2),
                [],
                ['mimetype' => 'application/pdf'],
            ],
            // options mimetype and website tags
            [
                ['websiteTags' => [0], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->mediaData, 5, 2),
                [],
                ['mimetype' => 'application/pdf'],
            ],
            // options type and admin tags
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->mediaData, 0, 3),
                [],
                ['type' => 'image'],
            ],
            // options mimetype and website tags
            [
                ['websiteTags' => [0], 'websiteTagsOperator' => 'or'],
                null,
                0,
                null,
                array_slice($this->mediaData, 5, 2),
                [],
                ['type' => 'document'],
            ],
            // options mimetype/type
            [
                [],
                null,
                0,
                null,
                array_slice($this->mediaData, 5, 3),
                [],
                ['mimetype' => 'application/pdf', 'type' => 'document'],
            ],
        ];
    }

    /**
     * @dataProvider findByProvider
     */
    public function testFindBy($filters, $page, $pageSize, $limit, $expected, $tags = [], $options = [])
    {
        $repository = $this->getContainer()->get('sulu_media.smart_content.data_provider.media.repository');

        // if tags isset replace the array indexes with database id
        if (array_key_exists('tags', $filters)) {
            $filters['tags'] = array_map(
                function ($tag) {
                    return $this->tags[$tag]->getId();
                },
                $filters['tags']
            );
        }

        // if tags isset replace the array indexes with database id
        if (array_key_exists('websiteTags', $filters)) {
            $filters['websiteTags'] = array_map(
                function ($tag) {
                    return $this->tags[$tag]->getId();
                },
                $filters['websiteTags']
            );
        }

        $result = $repository->findByFilters($filters, $page, $pageSize, $limit, 'de', $options);

        $length = count($expected);
        $this->assertCount($length, $result);

        for ($i = 0; $i < $length; ++$i) {
            $this->assertEquals($expected[$i][0], $result[$i]->getTitle(), $i);

            $existingTags = $result[$i]->getTags();
            foreach ($tags as $tag) {
                $this->assertContains($this->tags[$tag]->getName(), $existingTags);
            }
        }
    }
}

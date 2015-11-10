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

class MediaRepositoryFindByFiltersTest extends SuluTestCase
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
        ['Bild 1', [0, 1, 2]],
        ['Bild 2', [0, 1, 3]],
        ['Bild 3', [0, 1]],
        ['Bild 4', [0, 1, 2]],
        ['Bild 5', [0]],
        ['Bild 6', [0]],
        ['Bild 7', [0]],
        ['Bild 8', []],
    ];

    /**
     * @var MediaType
     */
    private $type;

    /**
     * @var Collection
     */
    private $collection;

    protected function setUp()
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->db('ORM')->getOm();

        $this->type = new MediaType();
        $this->type->setName('document');
        $this->type->setDescription('This is a document');
        $this->em->persist($this->type);

        $this->collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');
        $this->collection->setType($collectionType);
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($this->collection);
        $this->collection->addMeta($collectionMeta);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionType);
        $this->em->persist($this->collection);

        foreach ($this->tagData as $tag) {
            $this->tags[] = $this->createTag($tag);
        }
        $this->em->flush();

        foreach ($this->mediaData as $media) {
            $this->medias[] = $this->createMediaWithTags($media[0], $media[1]);
        }
        $this->em->flush();
    }

    private function createTag($name)
    {
        $tag = new Tag();
        $tag->setName($name);

        $this->em->persist($tag);

        return $tag;
    }

    private function createMediaWithTags($title, $tags = [])
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
        $fileVersion->setFile($file);
        $file->setVersion(1);
        $file->addFileVersion($fileVersion);
        $file->setMedia($media);
        $media->addFile($file);
        $media->setType($this->type);
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
        ];
    }

    /**
     * @dataProvider findByProvider
     */
    public function testFindBy($filters, $page, $pageSize, $limit, $expected, $tags = [])
    {
        $repository = $this->em->getRepository(Media::class);

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

        $result = $repository->findByFilters($filters, $page, $pageSize, $limit, 'de');

        $length = count($expected);
        $this->assertCount($length, $result);

        for ($i = 0; $i < $length; ++$i) {
            $file = $result[$i]->getFiles()->first();
            $fileVersion = $file->getFileVersions()->first();
            $meta = $fileVersion->getMeta()->first();
            $this->assertEquals($expected[$i][0], $meta->getTitle(), $i);

            foreach ($tags as $tag) {
                $this->assertTrue($fileVersion->getTags()->contains($this->tags[$tag]));
            }
        }
    }
}

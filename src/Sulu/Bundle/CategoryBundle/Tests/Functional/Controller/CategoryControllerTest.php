<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CategoryControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var CategoryInterface
     */
    private $category1;

    /**
     * @var CategoryInterface
     */
    private $category2;

    /**
     * @var CategoryInterface
     */
    private $category3;

    /**
     * @var CategoryInterface
     */
    private $category4;

    /**
     * @var CategoryInterface
     */
    private $meta1;

    public function setUp()
    {
        $this->em = $this->getEntityManager();

        $this->initOrm();
    }

    public function initOrm()
    {
        $this->purgeDatabase();
        /* First Category
        -------------------------------------*/
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey('first-category-key');
        $category->setDefaultLocale('en');

        // name for first category (en)
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('First Category');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        // name for first category (de)
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('de');
        $categoryTrans->setTranslation('Erste Kategorie');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);
        $this->category1 = $category;

        // meta for first category
        $categoryMeta = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta->setLocale('en');
        $categoryMeta->setKey('description');
        $categoryMeta->setValue('Description of Category');
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);
        $this->meta1 = $categoryMeta;

        $this->em->persist($category);

        /* Second Category
        -------------------------------------*/
        $category2 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category2->setKey('second-category-key');
        $category2->setDefaultLocale('en');
        $this->category2 = $category2;

        // name for second category
        $categoryTrans2 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans2->setLocale('en');
        $categoryTrans2->setTranslation('Second Category');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

        // name for second category
        $categoryTrans2 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans2->setLocale('de');
        $categoryTrans2->setTranslation('Zweite Kategorie');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

        // meta for second category
        $categoryMeta2 = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta2->setLocale('de');
        $categoryMeta2->setKey('description');
        $categoryMeta2->setValue('Beschreibung der zweiten Kategorie');
        $categoryMeta2->setCategory($category2);
        $category2->addMeta($categoryMeta2);

        // meta without locale for second category
        $categoryMeta3 = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta3->setKey('noLocaleKey');
        $categoryMeta3->setValue('noLocaleValue');
        $categoryMeta3->setCategory($category2);
        $category2->addMeta($categoryMeta3);

        $this->em->persist($category2);

        /* Third Category (child of first)
        -------------------------------------*/
        $category3 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category3->setParent($category);
        $category3->setDefaultLocale('en');
        $this->category3 = $category3;

        // name for third category
        $categoryTrans3 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans3->setLocale('en');
        $categoryTrans3->setTranslation('Third Category');
        $categoryTrans3->setCategory($category3);
        $category3->addTranslation($categoryTrans3);

        // meta for third category
        $categoryMeta4 = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta4->setLocale('de');
        $categoryMeta4->setKey('another');
        $categoryMeta4->setValue('Description of third Category');
        $categoryMeta4->setCategory($category3);
        $category3->addMeta($categoryMeta4);

        $this->em->persist($category3);

        /* Fourth Category (child of third)
        -------------------------------------*/
        $category4 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category4->setParent($category3);
        $category4->setDefaultLocale('en');
        $this->category4 = $category4;

        // name for fourth category
        $categoryTrans4 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans4->setLocale('en');
        $categoryTrans4->setTranslation('Fourth Category');
        $categoryTrans4->setCategory($category4);
        $category4->addTranslation($categoryTrans4);

        // meta for fourth category
        $categoryMeta5 = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta5->setLocale('de');
        $categoryMeta5->setKey('anotherkey');
        $categoryMeta5->setValue('Description of fourth Category');
        $categoryMeta5->setCategory($category4);
        $category4->addMeta($categoryMeta5);

        $this->em->persist($category4);

        $this->em->flush();
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('First Category', $response->name);
        $this->assertEquals('first-category-key', $response->key);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals($this->category1->getId(), $response->id);
        $this->assertEquals(1, count($response->meta));
        $this->assertEquals('description', $response->meta[0]->key);
        $this->assertEquals('Description of Category', $response->meta[0]->value);
    }

    public function testGetByIdChild()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories/' . $this->category3->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Third Category', $response->name);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals($this->category3->getId(), $response->id);
        $this->assertEquals($this->category1->getId(), $response->parent);
    }

    public function testGetByIdWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId()
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testGetByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/101230?locale=en'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    public function testGetByIdLocaleFallback()
    {
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setDefaultLocale('en');

        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('EN');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en_us');
        $categoryTrans->setTranslation('EN-US');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        $this->em->persist($category);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories/' . $category->getId() . '?locale=de'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals('EN', $response->name);

        $client->request(
            'GET',
            '/api/categories/' . $category->getId() . '?locale=en_us'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('en_us', $response->locale);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals('EN-US', $response->name);
    }

    public function testCGet()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(2, $categories);
        $this->assertCount(1, $categories[0]->children);
        $this->assertCount(1, $categories[0]->children[0]->children);

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('en', $categories[0]->locale);
        $this->assertEquals('first-category-key', $categories[0]->key);

        $this->assertEquals('second-category-key', $categories[1]->key);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertEquals('Third Category', $categories[0]->children[0]->name);
        $this->assertEquals('Fourth Category', $categories[0]->children[0]->children[0]->name);
    }

    public function testCGetFlat()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(2, $categories);
        $this->assertEquals(2, $response->total);

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('en', $categories[0]->locale);
        $this->assertEquals('first-category-key', $categories[0]->key);
        $this->assertTrue($categories[0]->hasChildren);

        $this->assertEquals('second-category-key', $categories[1]->key);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertFalse($categories[1]->hasChildren);
    }

    public function testCGetFlatWithExpandIds()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true&expandIds=' . $this->category4->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(4, $categories);
        $this->assertEquals(4, $response->total);

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('en', $categories[0]->locale);
        $this->assertEquals('first-category-key', $categories[0]->key);
        $this->assertTrue($categories[0]->hasChildren);

        $this->assertEquals('second-category-key', $categories[1]->key);
        $this->assertFalse($categories[1]->hasChildren);

        $this->assertEquals('Third Category', $categories[2]->name);
        $this->assertTrue($categories[2]->hasChildren);

        $this->assertEquals('Fourth Category', $categories[3]->name);
        $this->assertFalse($categories[3]->hasChildren);
    }

    public function testCGetFlatWithExpandIdsSameLevel()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true&expandIds=' . $this->category1->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(2, $categories);
        $this->assertEquals(2, $response->total);

        $this->assertEquals('First Category', $categories[0]->name);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('en', $categories[0]->locale);
        $this->assertEquals('first-category-key', $categories[0]->key);
        $this->assertTrue($categories[0]->hasChildren);

        $this->assertEquals('second-category-key', $categories[1]->key);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertFalse($categories[1]->hasChildren);
    }

    public function testCGetFlatWithSearch()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true&search=Third&searchFields=name'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $categories = $response->_embedded->categories;

        $this->assertCount(1, $categories);
        $this->assertEquals(1, $response->total);

        $this->assertEquals('Third Category', $categories[0]->name);
        $this->assertTrue($categories[0]->hasChildren);
    }

    public function testCGetWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories'
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testCGetFlatWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?flat=true'
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testCGetWithRoot()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&rootKey=' . $this->category1->getKey()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(1, $categories);
        $this->assertEquals($this->category3->getId(), $categories[0]->id);
        $this->assertEquals($this->category4->getId(), $categories[0]->children[0]->id);
    }

    public function testCGetFlatWithRoot()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $this->category1->getKey()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->category3->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);
    }

    public function testCGetFlatWithRootAndExpandIds()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $this->category1->getKey()
            . '&expandIds=' . $this->category4->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertEquals(2, $response->total);

        $this->assertEquals($this->category3->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);

        $this->assertEquals('Fourth Category', $categories[1]->name);
        $this->assertFalse($categories[1]->hasChildren);
    }

    public function testCGetFlatWithRootAndWrongExpandIds()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=' . $this->category1->getKey()
            . '&expandIds=' . $this->category2->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->category3->getId(), $categories[0]->id);
        $this->assertTrue($categories[0]->hasChildren);
    }

    public function testCGetWithNotExistingRoot()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&rootKey=101'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testCGetFlatWithNotExistingRoot()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en&flat=true&rootKey=101'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testCGetFlatWithSorting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=de&flat=true&sortBy=name&sortOrder=desc'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $categories = $response->_embedded->categories;

        $this->assertCount(2, $categories);

        $this->assertEquals('Zweite Kategorie', $categories[0]->name);
        $this->assertEquals('Erste Kategorie', $categories[1]->name);
    }

    public function testCGetLocaleFallback()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories?locale=de'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(2, $categories);

        $this->assertEquals('de', $categories[0]->locale);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('Erste Kategorie', $categories[0]->name);
        $this->assertEquals('de', $categories[1]->locale);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertEquals('Zweite Kategorie', $categories[1]->name);

        $client->request(
            'GET',
            '/api/categories?locale=de&rootKey=' . $this->category1->getKey()
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(1, $response->_embedded->categories);

        $this->assertEquals('en', $response->_embedded->categories[0]->locale);
        $this->assertEquals('en', $response->_embedded->categories[0]->defaultLocale);
        $this->assertEquals('Third Category', $response->_embedded->categories[0]->name);
    }

    public function testCGetFlatLocaleFallback()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories?locale=de&flat=true'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(2, $categories);

        $this->assertEquals('de', $categories[0]->locale);
        $this->assertEquals('en', $categories[0]->defaultLocale);
        $this->assertEquals('Erste Kategorie', $categories[0]->name);
        $this->assertEquals('de', $categories[1]->locale);
        $this->assertEquals('en', $categories[1]->defaultLocale);
        $this->assertEquals('Zweite Kategorie', $categories[1]->name);

        $client->request(
            'GET',
            '/api/categories?locale=de&flat=true&rootKey=' . $this->category1->getKey()
        );
        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertCount(1, $response->_embedded->categories);

        $this->assertEquals('en', $response->_embedded->categories[0]->locale);
        $this->assertEquals('en', $response->_embedded->categories[0]->defaultLocale);
        $this->assertEquals('Third Category', $response->_embedded->categories[0]->name);
    }

    public function testGetChildren()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '/children?locale=en'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(1, $response->_embedded->categories);
        $this->assertEquals($this->category3->getId(), $response->_embedded->categories[0]->id);
    }

    public function testGetChildrenFlat()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '/children?locale=en&flat=true'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(1, $response->_embedded->categories);
        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->category3->getId(), $response->_embedded->categories[0]->id);
    }

    public function testGetChildrenWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '/children'
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testGetChildrenFlatWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '/children?flat=true'
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testGetChildrenWithNotExistingParent()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/1001/children?locale=de'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testGetChildrenFlatWithNotExistingParent()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/1001/children?locale=de&flat=true'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPost()
    {
        $collection = $this->createCollection();
        $type = $this->createImageType();

        $medias = [
            $this->createMedia('test-1', $type, $collection),
            $this->createMedia('test-2', $type, $collection),
            $this->createMedia('test-3', $type, $collection),
        ];

        $ids = array_map(
            function (Media $media) {
                return $media->getId();
            },
            $medias
        );

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories?locale=en',
            [
                'name' => 'New Category',
                'description' => 'Sulu is awesome',
                'medias' => ['ids' => $ids],
                'key' => 'new-category-key',
                'meta' => [
                    [
                        'key' => 'myKey',
                        'value' => 'myValue',
                    ],
                    [
                        'key' => 'anotherKey',
                        'value' => 'should not be visible due to locale',
                        'locale' => 'de-ch',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('Sulu is awesome', $response->description);
        $this->assertEquals(['ids' => $ids], (array) $response->medias);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals(1, count($response->meta));
        $this->assertEquals('myKey', $response->meta[0]->key);
        $this->assertEquals('myValue', $response->meta[0]->value);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories/' . $response->id . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals(1, count($response->meta));
        $this->assertEquals('myKey', $response->meta[0]->key);
        $this->assertEquals('myValue', $response->meta[0]->value);
    }

    public function testPostWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories',
            [
                'name' => 'New Category',
                'key' => 'new-category-key',
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testPostWithExistingKey()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories?locale=en',
            [
                'name' => 'New Category',
                'key' => 'first-category-key',
            ]
        );

        $this->assertHttpStatusCode(409, $client->getResponse());
    }

    public function testPostWithoutName()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/categories?locale=en',
            [
                'key' => 'new-category-key',
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testPostWithParent()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(1, $response->children);

        $client->request(
            'POST',
            '/api/categories?locale=en',
            [
                'name' => 'New Category',
                'key' => 'new-category-key',
                'parent' => $this->category1->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('New Category', $response->name);
        $this->assertEquals('new-category-key', $response->key);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals($this->category1->getId(), $response->parent);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(2, $response->children);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '?locale=en',
            [
                'name' => 'Modified Category',
                'meta' => [
                    [
                        'id' => $this->meta1->getId(),
                        'key' => 'modifiedKey',
                        'value' => 'This meta got overriden',
                        'locale' => null,
                    ],
                    [
                        'key' => 'newMeta',
                        'value' => 'This meta got added',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Modified Category', $response->name);
        $this->assertObjectNotHasAttribute('key', $response);
        $this->assertEquals('en', $response->defaultLocale);
        $this->assertEquals(2, count($response->meta));

        usort(
            $response->meta,
            function ($m1, $m2) {
                return strcmp($m1->key, $m2->key);
            }
        );
        $this->assertTrue('modifiedKey' === $response->meta[0]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[0]->value);
        $this->assertTrue('newMeta' === $response->meta[1]->key);
        $this->assertTrue('This meta got added' === $response->meta[1]->value);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Modified Category', $response->name);
        $this->assertObjectNotHasAttribute('key', $response);
        $this->assertEquals(2, count($response->meta));

        usort(
            $response->meta,
            function ($m1, $m2) {
                return strcmp($m1->key, $m2->key);
            }
        );
        $this->assertTrue('modifiedKey' === $response->meta[0]->key);
        $this->assertTrue('This meta got overriden' === $response->meta[0]->value);
        $this->assertTrue('newMeta' === $response->meta[1]->key);
        $this->assertTrue('This meta got added' === $response->meta[1]->value);
    }

    public function testPutWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId(),
            [
                'name' => 'Modified Category',
                'key' => 'modified-category-key',
                'meta' => [
                    [
                        'id' => $this->meta1->getId(),
                        'key' => 'modifiedKey',
                        'value' => 'This meta got overriden',
                        'locale' => null,
                    ],
                    [
                        'key' => 'newMeta',
                        'value' => 'This meta got added',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testPutWithDifferentLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '?locale=cn',
            [
                'name' => 'Imagine this is chinese',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Imagine this is chinese', $response->name);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=cn'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Imagine this is chinese', $response->name);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('First Category', $response->name);
    }

    public function testPutWithoutName()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category1->getId() . '?locale=en',
            [
                'meta' => [
                    [
                        'key' => 'newMeta',
                        'value' => 'This meta got added',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testPutWithExistingKey()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/' . $this->category2->getId() . '?locale=en',
            [
                'name' => 'New Category',
                'key' => 'first-category-key',
            ]
        );

        $this->assertHttpStatusCode(409, $client->getResponse());
    }

    public function testPutNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/categories/101?locale=en',
            [
                'name' => 'No existing Category',
                'meta' => [
                    [
                        'key' => 'newMeta',
                        'value' => 'This meta got added',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPatch()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/categories/' . $this->category1->getId() . '?locale=en',
            [
                'name' => 'Name changed through patch',
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals($this->category1->getId(), $response->id);
        $this->assertEquals('Name changed through patch', $response->name);
        $this->assertEquals('first-category-key', $response->key);

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals($this->category1->getId(), $response->id);
        $this->assertEquals('Name changed through patch', $response->name);
        $this->assertEquals('first-category-key', $response->key);
    }

    public function testPatchChangeParent()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(1, $categories[0]->children);
        $this->assertCount(1, $categories[0]->children[0]->children);
        $this->assertCount(0, $categories[0]->children[0]->children[0]->children);
        $this->assertCount(0, $categories[1]->children);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/categories/' . $this->category4->getId() . '?locale=en',
            [
                'parent' => $this->category2->getId(),
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/categories/' . $this->category1->getId() . '?locale=en'
        );

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $categories = $response->_embedded->categories;
        usort(
            $categories,
            function ($cat1, $cat2) {
                return $cat1->id > $cat2->id;
            }
        );

        $this->assertCount(1, $categories[0]->children);
        $this->assertCount(0, $categories[0]->children[0]->children);
        $this->assertCount(1, $categories[1]->children);
        $this->assertCount(0, $categories[0]->children[0]->children);
    }

    public function testPatchWithExistingKey()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/categories/' . $this->category3->getId() . '?locale=en',
            [
                'key' => 'first-category-key',
            ]
        );

        $this->assertHttpStatusCode(409, $client->getResponse());
    }

    public function testPatchNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/categories/101?locale=en',
            [
                'name' => 'Not existing Category',
            ]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/categories/' . $this->category2->getId()
        );

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories' . $this->category2->getId()
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/categories/101'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteWithChildren()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/categories/' . $this->category1->getId()
        );

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories?locale=en'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, count($response->_embedded->categories));
        $this->assertEquals($this->category2->getId(), $response->_embedded->categories[0]->id);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/categories' . $this->category4->getId()
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    protected function createImageType()
    {
        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');

        $this->em->persist($imageType);
        $this->em->flush();

        return $imageType;
    }

    protected function createCollection()
    {
        $collection = new Collection();
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');
        $collection->setType($collectionType);

        $this->em->persist($collection);
        $this->em->persist($collectionType);
        $this->em->flush();

        return $collection;
    }

    protected function createMedia($name, $type, $collection, $locale = 'en')
    {
        $media = new Media();
        $media->setType($type);
        $extension = 'jpeg';
        $mimeType = 'image/jpg';

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.' . $extension);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale($locale);
        $fileVersionMeta->setTitle($name);
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $media->addFile($file);
        $media->setCollection($collection);

        $this->em->persist($media);
        $this->em->persist($file);
        $this->em->persist($fileVersionMeta);
        $this->em->persist($fileVersion);
        $this->em->flush();

        return $media;
    }
}

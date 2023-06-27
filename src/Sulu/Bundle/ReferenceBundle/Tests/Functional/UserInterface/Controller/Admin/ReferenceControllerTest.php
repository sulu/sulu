<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Functional\UserInterface\Controller\Admin;

use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ReferenceControllerTest extends SuluTestCase
{
    public function testCgetActionEmpty(): void
    {
        $client = $this->createAuthenticatedClient();

        self::purgeDatabase();

        $client->request('GET', '/admin/api/references?resourceKey=media&resourceId=1');

        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $json */
        $json = \json_decode($response->getContent() ?: '', true, \JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                '_embedded' => [
                    'references' => [],
                ],
                'limit' => 10,
                'total' => 0,
                'page' => 1,
                'pages' => 1,
            ],
            $json
        );
    }

    public function testCgetActionFilters(): void
    {
        $client = $this->createAuthenticatedClient();

        self::purgeDatabase();

        $this->createReference('media', '1', 'pages', '123-123');
        $this->createReference('media', '1', 'pages', '456-789');
        $this->createReference('media', '2', 'pages', '890-123');

        self::getEntityManager()->flush();

        $client->request('GET', '/admin/api/references?resourceKey=media&resourceId=1');
        $response = $client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        /** @var array<string, mixed> $json */
        $json = \json_decode($response->getContent() ?: '', true, \JSON_THROW_ON_ERROR);

        $this->assertTrue(isset($json['_embedded']));
        $this->assertIsArray($json['_embedded']);
        $this->assertTrue(isset($json['_embedded']['references']));
        $this->assertIsArray($json['_embedded']['references']);
        $this->assertTrue(isset($json['_embedded']['references'][0]['id']));
        $this->assertTrue(isset($json['_embedded']['references'][1]['id']));
        // replace ids with fixed values
        foreach ($json['_embedded']['references'] as $key => &$reference) {
            $reference['id'] = $key + 1;
        }

        $this->assertSame(
            [
                '_embedded' => [
                    'references' => [
                        [
                            'referenceTitle' => 'Title',
                            'referenceResourceKeyTitle' => 'Page',
                            'referenceResourceId' => '123-123',
                            'referenceProperty' => 'image',
                            'resourceId' => '1',
                            'resourceKey' => 'media',
                            'referenceResourceKey' => 'pages',
                            'referenceViewAttributes' => ['webspace' => 'sulu'],
                            'id' => 1,
                        ],
                        [
                            'referenceTitle' => 'Title',
                            'referenceResourceKeyTitle' => 'Page',
                            'referenceResourceId' => '456-789',
                            'referenceProperty' => 'image',
                            'resourceId' => '1',
                            'resourceKey' => 'media',
                            'referenceResourceKey' => 'pages',
                            'referenceViewAttributes' => ['webspace' => 'sulu'],
                            'id' => 2,
                        ],
                    ],
                ],
                'limit' => 10,
                'total' => 2,
                'page' => 1,
                'pages' => 1,
            ],
            $json,
        );
    }

    private function createReference(
        string $resourceKey,
        string $resourceId,
        string $referenceResourceKey,
        string $referenceResourceId,
    ): Reference {
        $reference = new Reference();
        $reference->setResourceKey($resourceKey);
        $reference->setResourceId($resourceId);
        $reference->setReferenceResourceKey($referenceResourceKey);
        $reference->setReferenceResourceId($referenceResourceId);
        $reference->setReferenceLocale('en');
        $reference->setReferenceTitle('Title');
        $reference->setReferenceProperty('image');
        $reference->setReferenceCount(1);
        $reference->setReferenceLiveCount(1);
        $reference->setReferenceViewAttributes(['webspace' => 'sulu']);

        self::getEntityManager()->persist($reference);

        return $reference;
    }
}

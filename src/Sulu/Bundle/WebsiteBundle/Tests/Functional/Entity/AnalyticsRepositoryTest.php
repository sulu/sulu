<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Entity;

use Sulu\Bundle\WebsiteBundle\Tests\Functional\BaseFunctional;

class AnalyticsRepositoryTest extends BaseFunctional
{
    public function testFindByWebspaceKey(): void
    {
        $this->purgeDatabase();
        $this->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'test']],
            ]
        );
        $this->create(
            'sulu_io',
            [
                'title' => 'test-2',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'test']],
            ]
        );

        $result = $this->analyticsRepository->findByWebspaceKey('sulu_io', 'test');
        $this->assertCount(2, $result);

        $this->assertEquals('test-1', $result[0]->getTitle());
        $this->assertEquals('test-2', $result[1]->getTitle());

        $result = $this->analyticsRepository->findByWebspaceKey('test_io', 'test');
        $this->assertEmpty($result);
    }

    public function testFindById(): void
    {
        $entity = $this->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'prod']],
            ]
        );

        $id = $entity->getId();
        $this->assertNotNull($id);
        $result = $this->analyticsRepository->findById($id);
        $this->assertEquals('test-1', $result->getTitle());
    }

    public function testFindByUrl(): void
    {
        $this->purgeDatabase();
        $this->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'allDomains' => false,
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'prod']],
            ]
        );
        $this->create(
            'sulu_io',
            [
                'title' => 'test-2',
                'type' => 'google',
                'content' => 'UA123-123',
                'allDomains' => true,
                'domains' => [],
            ]
        );

        $result = $this->analyticsRepository->findByUrl('www.sulu.io/{localization}', 'sulu_io', 'prod');
        $this->assertCount(2, $result);

        $this->assertEquals('test-1', $result[0]->getTitle());
        $this->assertEquals('test-2', $result[1]->getTitle());

        $result = $this->analyticsRepository->findByUrl('www.sulu.io/{localization}', 'sulu_io', 'dev');
        $this->assertCount(1, $result);

        $this->assertEquals('test-2', $result[0]->getTitle());

        $result = $this->analyticsRepository->findByUrl('www.sulu.ud', 'sulu_io', 'stage');
        $this->assertCount(1, $result);

        $this->assertEquals('test-2', $result[0]->getTitle());
    }

    public function testFindByUrlDifferentWebspaces(): void
    {
        $this->purgeDatabase();
        $this->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'allDomains' => true,
                'domains' => [],
            ]
        );
        $this->create(
            'test_io',
            [
                'title' => 'test-2',
                'type' => 'google',
                'content' => 'UA123-123',
                'allDomains' => true,
                'domains' => [],
            ]
        );

        $result = $this->analyticsRepository->findByUrl('www.sulu.io/{localization}', 'sulu_io', 'prod');
        $this->assertCount(1, $result);
        $this->assertEquals('test-1', $result[0]->getTitle());

        $result = $this->analyticsRepository->findByUrl('www.sulu.io/{localization}', 'test_io', 'prod');
        $this->assertCount(1, $result);
        $this->assertEquals('test-2', $result[0]->getTitle());
    }
}

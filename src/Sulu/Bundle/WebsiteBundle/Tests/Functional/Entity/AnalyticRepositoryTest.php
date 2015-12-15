<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Entity;

use Functional\BaseFunctional;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticRepository;

require_once __DIR__ . '/../BaseFunctional.php';

class AnalyticRepositoryTest extends BaseFunctional
{
    /**
     * @var AnalyticRepository
     */
    private $analyticRepository;

    public function setUp()
    {
        parent::setUp();

        $this->analyticRepository = $this->getContainer()->get('sulu_website.analytics.repository');
    }

    public function testFindByWebspaceKey()
    {
        $this->purgeDatabase();
        $this->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'prod']],
            ]
        );
        $this->create(
            'sulu_io',
            [
                'title' => 'test-2',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'prod']],
            ]
        );

        $result = $this->analyticRepository->findByWebspaceKey('sulu_io');
        $this->assertCount(2, $result);

        $this->assertEquals('test-1', $result[0]->getTitle());
        $this->assertEquals('test-2', $result[1]->getTitle());

        $result = $this->analyticRepository->findByWebspaceKey('test_io');
        $this->assertEmpty($result);
    }

    public function testFindById()
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

        $result = $this->analyticRepository->findById($entity->getId());
        $this->assertEquals('test-1', $result->getTitle());
    }

    public function testFindByUrl()
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

        $result = $this->analyticRepository->findByUrl('www.sulu.io/{localization}', 'prod');
        $this->assertCount(2, $result);

        $this->assertEquals('test-1', $result[0]->getTitle());
        $this->assertEquals('test-2', $result[1]->getTitle());

        $result = $this->analyticRepository->findByUrl('www.sulu.io/{localization}', 'dev');
        $this->assertCount(1, $result);

        $this->assertEquals('test-2', $result[0]->getTitle());

        $result = $this->analyticRepository->findByUrl('www.sulu.ud', 'stage');
        $this->assertCount(1, $result);

        $this->assertEquals('test-2', $result[0]->getTitle());
    }
}

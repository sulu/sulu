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

class DomainRepositoryTest extends BaseFunctional
{
    public function testFindByUrlAndEnvironment(): void
    {
        $domain = $this->findOrCreateNewDomain(['url' => 'sulu.io', 'environment' => 'dev']);
        $this->entityManager->flush();

        $result = $this->domainRepository->findByUrlAndEnvironment('sulu.io', 'dev');
        $this->assertEquals($domain->getId(), $result->getId());

        $result = $this->domainRepository->findByUrlAndEnvironment('sulu.io', 'stage');
        $this->assertNull($result);

        $result = $this->domainRepository->findByUrlAndEnvironment('test.io', 'dev');
        $this->assertNull($result);
    }
}

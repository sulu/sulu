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

require_once __DIR__ . '/../BaseFunctional.php';

class DomainRepositoryTest extends BaseFunctional
{
    public function testFindByUrlAndEnvironment()
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

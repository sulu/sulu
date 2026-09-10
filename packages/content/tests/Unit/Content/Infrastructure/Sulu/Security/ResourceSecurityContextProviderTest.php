<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Security;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Infrastructure\Sulu\Security\ResourceSecurityContextProvider;

#[CoversClass(ResourceSecurityContextProvider::class)]
class ResourceSecurityContextProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testFixedContextResourcesDoNotLoadTheEntity(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->find(\Prophecy\Argument::cetera())->shouldNotBeCalled();

        $provider = new ResourceSecurityContextProvider($entityManager->reveal(), \stdClass::class, 'sulu.article.articles');

        $this->assertEquals(
            new SecurityCondition('sulu.article.articles', 'en'),
            $provider->resolve('article-id-1', 'en'),
        );
    }

    public function testSecuredEntitiesCarryTheirContextAndObjectIdentity(): void
    {
        $entity = new class() implements SecuredEntityInterface {
            public function getId(): string
            {
                return 'page-uuid';
            }

            public function getSecurityContext(): string
            {
                return 'sulu.webspaces.example';
            }
        };

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->find($entity::class, 'page-uuid')->willReturn($entity);

        $provider = new ResourceSecurityContextProvider($entityManager->reveal(), $entity::class);

        $this->assertEquals(
            new SecurityCondition('sulu.webspaces.example', 'de', $entity::class, 'page-uuid'),
            $provider->resolve('page-uuid', 'de'),
        );
    }

    public function testThrowsWhenTheEntityCarriesNoSecurityContext(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->find(\stdClass::class, 'missing')->willReturn(null);

        $provider = new ResourceSecurityContextProvider($entityManager->reveal(), \stdClass::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve a security context from "stdClass" with id "missing".');

        $provider->resolve('missing', 'en');
    }
}

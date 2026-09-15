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
use Sulu\Content\Infrastructure\Sulu\Security\ResourceSecurityContextProvider;
use Sulu\Content\Infrastructure\Sulu\Security\WorkflowTransitionRequestSecurityContextResolver;

#[CoversClass(WorkflowTransitionRequestSecurityContextResolver::class)]
class WorkflowTransitionRequestSecurityContextResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveDelegatesToTheProviderOfTheResourceKey(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);

        $resolver = new WorkflowTransitionRequestSecurityContextResolver([
            'articles' => new ResourceSecurityContextProvider($entityManager->reveal(), \stdClass::class, 'sulu.article.articles'),
            'snippets' => new ResourceSecurityContextProvider($entityManager->reveal(), \stdClass::class, 'sulu.global.snippets'),
        ]);

        $this->assertSame('sulu.article.articles', $resolver->resolve('articles', 'article-id-1', 'en')->getSecurityContext());
        $this->assertSame('sulu.global.snippets', $resolver->resolve('snippets', 'snippet-id-1', 'de')->getSecurityContext());
    }

    public function testResolveThrowsWhenNoProviderMatches(): void
    {
        $resolver = new WorkflowTransitionRequestSecurityContextResolver([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No security context provider registered for resource key "unknown"');

        $resolver->resolve('unknown', 'anything', 'en');
    }
}
